<?php

declare(strict_types=1);

namespace DMF\Import\Score;

use DMF\Import\Audit\AuditTrailService;
use DMF\Import\Audit\DuplicateDetectionService;
use DMF\Import\Contracts\MappingInterface;
use DMF\Import\Contracts\ParserInterface;
use DMF\Import\ImportJobManager;
use DMF\Import\Template\ImportTemplate;
use DMF\Repository\ImportJobRepository;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Orchestrates one queued import job end to end: parse → map → check for
 * duplicates → validate → resolve → normalize → commit (all-or-nothing,
 * FR-006's "no partial commits"), logging each pipeline stage per FR-008.
 *
 * Reuses the whole Import Engine Foundation (T2.1) and Template Registry
 * (T2.2) rather than re-implementing any of it: ParserInterface (chosen by
 * `import_jobs.file_type`), MappingInterface, ImportTemplate/TemplateRegistry
 * (the caller resolves *which* template applies and passes it in — this
 * service does not guess a template key from assessment metadata, since no
 * documented convention establishes one), ImportJobManager for every state
 * transition and audit log entry.
 *
 * T2.6 (FR-007/FR-008) adds two more collaborators: DuplicateDetectionService
 * (checked once, right after mapping, before template validation — a
 * duplicate file shouldn't need column-level validation to be rejected) and
 * AuditTrailService (the four new event types decisions/IDR-008 added:
 * `import_started`, `duplicate_found`, `rollback` — `retry` is
 * DMF\Import\Session\RetryFailedImport's concern, not this class's).
 *
 * Deliberately excludes: analytics (standard_performance_summary recompute),
 * AI diagnostics, dashboards — none of this pipeline's job.
 */
final class ScoreImportService
{
    /** @param array<string, ParserInterface> $parsers Keyed by import_jobs.file_type ("xlsx", "csv"). */
    public function __construct(
        private readonly ImportJobRepository $jobs,
        private readonly ImportJobManager $jobManager,
        private readonly array $parsers,
        private readonly MappingInterface $mapper,
        private readonly RowValidator $rowValidator,
        private readonly StudentResolver $studentResolver,
        private readonly AssessmentResolver $assessmentResolver,
        private readonly ScoreNormalizer $scoreNormalizer,
        private readonly ImportTransactionService $transactionService,
        private readonly DuplicateDetectionService $duplicateDetection,
        private readonly AuditTrailService $auditTrail,
    ) {
    }

    /** @throws RuntimeException If the job does not exist. */
    public function import(int $importJobId, ImportTemplate $template): ImportResult
    {
        $job = $this->jobs->findById($importJobId);

        if ($job === null) {
            throw new RuntimeException(sprintf('Import job %d not found.', $importJobId));
        }

        if ($job['status'] !== 'queued') {
            throw new InvalidArgumentException(
                sprintf('Import job %d is not queued (status: %s).', $importJobId, (string) $job['status']),
            );
        }

        $schoolId = (int) $job['school_id'];

        $this->jobManager->markProcessing($importJobId);
        $this->auditTrail->recordImportStarted($importJobId, $schoolId);

        $assessment = $this->assessmentResolver->resolve((int) $job['assessment_id']);

        if ($assessment === null) {
            return $this->fail($importJobId, 0, sprintf('Assessment %d not found.', $job['assessment_id']));
        }

        $parser = $this->parsers[$job['file_type']] ?? null;

        if ($parser === null) {
            return $this->fail(
                $importJobId,
                0,
                sprintf('No parser registered for file type "%s".', $job['file_type']),
            );
        }

        try {
            $parsedFile = $parser->parse((string) $job['file_path']);
        } catch (RuntimeException $e) {
            return $this->fail($importJobId, 0, $e->getMessage());
        }

        $this->jobManager->log($importJobId, 'parsed');

        $mappingResult = $this->mapper->map($parsedFile, $template->mapping);
        $this->jobManager->log($importJobId, 'mapped');

        if ($mappingResult->mappedRows === []) {
            return $this->fail($importJobId, 0, 'No data rows found in file.');
        }

        $duplicateCheck = $this->duplicateDetection->detect(
            $importJobId,
            $schoolId,
            (int) $job['assessment_id'],
            (string) $job['file_path'],
            $mappingResult->mappedRows,
        );

        if ($duplicateCheck->hasDuplicates()) {
            $this->auditTrail->recordDuplicateFound($importJobId, $schoolId, $duplicateCheck);

            return $this->fail($importJobId, count($mappingResult->mappedRows), $duplicateCheck->summary());
        }

        $validationResults = $this->rowValidator->validate($mappingResult->mappedRows, $template);

        $rowErrors = [];
        $rowsToCommit = [];

        foreach ($mappingResult->mappedRows as $index => $row) {
            $errors = [];

            foreach ($validationResults[$index]->errors() as $messages) {
                foreach ($messages as $message) {
                    $errors[] = $message;
                }
            }

            $studentId = $row['student_id'] ?? '';

            if ($studentId !== '' && $this->studentResolver->resolve($studentId) === null) {
                $errors[] = sprintf('No student found with student_id "%s".', $studentId);
            }

            $rawScore = $row['score'] ?? '';
            $normalizedScore = null;

            // A missing/blank score is always an error here, regardless of
            // whether the caller's ImportTemplate happens to mark "score" as
            // required — this pipeline cannot commit a null score, so it does
            // not rely on template configuration to guarantee that; it
            // guarantees it directly.
            if ($rawScore === '') {
                $errors[] = '"score" is required but missing or empty.';
            } else {
                try {
                    $normalizedScore = $this->scoreNormalizer->normalize($rawScore);
                } catch (InvalidArgumentException $e) {
                    $errors[] = $e->getMessage();
                }
            }

            if ($errors !== [] || $normalizedScore === null) {
                // +2: row index 0 is the first row *after* the header, i.e. file row 2.
                $rowErrors[] = sprintf('Row %d: %s', $index + 2, implode('; ', $errors));

                continue;
            }

            $rowsToCommit[] = [
                'student_id' => $studentId,
                'assessment_id' => (int) $job['assessment_id'],
                'score' => $normalizedScore,
                'import_job_id' => $importJobId,
            ];
        }

        if ($rowErrors !== []) {
            $this->jobManager->markFailed($importJobId, implode(' | ', $rowErrors));

            return ImportResult::failure($importJobId, count($mappingResult->mappedRows), $rowErrors);
        }

        $this->jobManager->log($importJobId, 'validated');

        try {
            $committed = $this->transactionService->commit($rowsToCommit);
        } catch (Throwable $e) {
            $this->auditTrail->recordRollback($importJobId, $schoolId);

            return $this->fail(
                $importJobId,
                count($mappingResult->mappedRows),
                sprintf('Commit failed: %s', $e->getMessage()),
            );
        }

        $this->jobManager->markCommitted($importJobId);

        return ImportResult::success($importJobId, $committed);
    }

    private function fail(int $importJobId, int $totalRows, string $message): ImportResult
    {
        $this->jobManager->markFailed($importJobId, $message);

        return ImportResult::failure($importJobId, $totalRows, [$message]);
    }
}
