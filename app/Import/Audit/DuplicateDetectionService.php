<?php

declare(strict_types=1);

namespace DMF\Import\Audit;

use DMF\Repository\ImportJobRepository;
use DMF\Repository\StudentScoreRepository;

/**
 * FR-007 (Duplicate Import Detection). Given one import job's already-parsed
 * and mapped rows, checks all four duplicate signals
 * docs/01-PRD.md FR-007 and this task's scope call for:
 *
 * 1. Within-file: the same student_id repeated in the file being imported.
 * 2. Already imported: a student_id in this file already has a committed
 *    `student_scores` row for the same assessment (the operative FR-007
 *    check per that requirement's own Business Rule — "uniqueness is
 *    checked on (academic year, subject, student ID)", which for this
 *    schema reduces to (student_id, assessment_id) since one assessment row
 *    already denotes exactly one academic_year+subject+assessment_type
 *    combination).
 * 3. Duplicate import job: another `import_jobs` row already exists for the
 *    exact same (school, assessment, file_path) — reuses
 *    ImportJobRepository::findBySchoolAssessmentAndPath(), an existing T2.1
 *    method that was written for this purpose but never wired in until now.
 * 4. Active duplicate job: another job for the same school+assessment is
 *    still `queued`/`processing` (a race, not yet a committed duplicate) —
 *    reuses the new ImportJobRepository::findActiveJobsForSchoolAndAssessment().
 *
 * Also computes a content hash of the mapped rows (student_id + score
 * pairs). "Hash comparison if available" per this task's scope: no
 * `file_hash`/checksum column exists anywhere in the schema (confirmed
 * against docs/03-Database-Design.md — introducing one is a schema change
 * out of this task's scope), so the hash is exposed on DuplicateCheckResult
 * for a caller/future feature to persist and compare, not compared against
 * import history here. See this class's test suite and PROJECT_BOARD.md's
 * Known Limitations for T2.6.
 *
 * Deliberately does **not** delegate the within-file check to
 * DMF\Import\Score\RowValidator, even though that class already implements
 * one: RowValidator returns per-row template-validation error strings
 * (ValidationResult), not the structured, aggregate DuplicateCheckResult
 * FR-007's audit trail needs. The two independently detect the same
 * within-file condition by design — RowValidator remains a defense-in-depth
 * check inside template validation; this class is the first, fail-fast
 * check the pipeline runs, before template validation is ever reached.
 *
 * Never silently ignores a duplicate: detect() always returns a
 * DuplicateCheckResult, never null/void, and every signal it can detect is
 * represented on that result, not swallowed.
 */
final class DuplicateDetectionService
{
    public function __construct(
        private readonly StudentScoreRepository $scores,
        private readonly ImportJobRepository $jobs,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $mappedRows Rows already parsed+mapped
     *     (at minimum: student_id, score) — the same shape ScoreImportService's row loop works
     *     against.
     */
    public function detect(
        int $importJobId,
        int $schoolId,
        int $assessmentId,
        string $filePath,
        array $mappedRows,
    ): DuplicateCheckResult {
        $withinFile = $this->detectWithinFile($mappedRows);
        $alreadyImported = $this->detectAlreadyImported($assessmentId, $mappedRows);

        $duplicateJob = $this->jobs->findBySchoolAssessmentAndPath($schoolId, $assessmentId, $filePath);

        if ($duplicateJob !== null && (int) $duplicateJob['id'] === $importJobId) {
            // The only row with this exact path is this job's own — not a duplicate of itself.
            $duplicateJob = null;
        }

        $activeJobs = $this->jobs->findActiveJobsForSchoolAndAssessment($schoolId, $assessmentId, $importJobId);
        $activeJobIds = array_map(static fn (array $job): int => (int) $job['id'], $activeJobs);

        return new DuplicateCheckResult(
            $withinFile,
            $alreadyImported,
            $duplicateJob,
            $activeJobIds,
            $this->contentHash($mappedRows),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $mappedRows
     * @return array<string, int[]>
     */
    private function detectWithinFile(array $mappedRows): array
    {
        /** @var array<string, int> $firstSeenAtRow */
        $firstSeenAtRow = [];
        /** @var array<string, int[]> $duplicates */
        $duplicates = [];

        foreach (array_values($mappedRows) as $index => $row) {
            $studentId = (string) ($row['student_id'] ?? '');

            if ($studentId === '') {
                continue;
            }

            // +2: row index 0 is the first row after the header, i.e. file row 2 — matches
            // ScoreImportService's own row-numbering convention.
            $rowNumber = $index + 2;

            if (isset($firstSeenAtRow[$studentId])) {
                if (!isset($duplicates[$studentId])) {
                    $duplicates[$studentId] = [$firstSeenAtRow[$studentId]];
                }

                $duplicates[$studentId][] = $rowNumber;

                continue;
            }

            $firstSeenAtRow[$studentId] = $rowNumber;
        }

        return $duplicates;
    }

    /**
     * @param array<int, array<string, mixed>> $mappedRows
     * @return array<int, string>
     */
    private function detectAlreadyImported(int $assessmentId, array $mappedRows): array
    {
        $duplicates = [];

        foreach (array_values($mappedRows) as $index => $row) {
            $studentId = (string) ($row['student_id'] ?? '');

            if ($studentId === '') {
                continue;
            }

            if ($this->scores->existsForStudentAndAssessment($studentId, $assessmentId)) {
                $duplicates[$index + 2] = $studentId;
            }
        }

        return $duplicates;
    }

    /**
     * Canonical content hash of this file's (student_id, score) pairs, order-independent —
     * two files with the same student/score pairs in a different row order hash identically.
     *
     * @param array<int, array<string, mixed>> $mappedRows
     */
    public function contentHash(array $mappedRows): string
    {
        $canonical = [];

        foreach ($mappedRows as $row) {
            $canonical[] = [(string) ($row['student_id'] ?? ''), (string) ($row['score'] ?? '')];
        }

        sort($canonical);

        return hash('sha256', (string) json_encode($canonical));
    }
}
