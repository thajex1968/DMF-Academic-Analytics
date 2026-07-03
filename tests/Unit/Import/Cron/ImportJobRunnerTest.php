<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Cron;

use DMF\Import\Audit\AuditTrailService;
use DMF\Import\Audit\DuplicateDetectionService;
use DMF\Import\Audit\ImportAuditLogger;
use DMF\Import\Cron\ImportJobRunner;
use DMF\Import\ImportJobManager;
use DMF\Import\Parser\CsvParser;
use DMF\Import\Parser\ExcelParser;
use DMF\Import\Score\AssessmentResolver;
use DMF\Import\Score\ImportTransactionService;
use DMF\Import\Score\RowValidator;
use DMF\Import\Score\ScoreImportService;
use DMF\Import\Score\ScoreNormalizer;
use DMF\Import\Score\StudentResolver;
use DMF\Import\Session\ImportSessionService;
use DMF\Import\Template\ColumnMapper;
use DMF\Import\Template\ExampleTemplates;
use DMF\Import\Template\TemplateRegistry;
use DMF\Import\Template\TemplateResolver;
use DMF\Import\Template\TemplateValidator;
use DMF\Repository\AssessmentRepository;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\ImportLogRepository;
use DMF\Repository\StudentRepository;
use DMF\Repository\StudentScoreRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ImportJobRunner exercised end to end: real ImportJobManager,
 * ImportSessionService/ScoreImportService (with T2.6's Duplicate
 * Detection/Audit Trail), and TemplateResolver, all over one stateful
 * mocked ConnectionInterface — same pattern as ScoreImportServiceTest.
 */
final class ImportJobRunnerTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../../../fixtures/import';

    /** @var array<string, mixed> */
    private array $state;

    private ConnectionInterface $connection;

    protected function setUp(): void
    {
        $this->state = [
            'jobs' => [],
            'assessments' => [
                3 => ['id' => 3, 'subject_code' => 'MATH', 'academic_year' => 2569],
                4 => ['id' => 4, 'subject_code' => 'MATH', 'academic_year' => 2570],
            ],
            'students' => [
                'S001' => ['student_id' => 'S001', 'full_name' => 'Student One'],
                'S002' => ['student_id' => 'S002', 'full_name' => 'Student Two'],
                'S003' => ['student_id' => 'S003', 'full_name' => 'Student Three'],
            ],
            'logs' => [],
            'scores' => [],
        ];

        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturnCallback(fn (): string => (string) (count($this->state['scores']) + 1));

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('pdo')->willReturn($pdo);
        $connection->method('transaction')->willReturnCallback(static fn (callable $callback) => $callback());
        $connection->method('execute')->willReturnCallback(
            fn (string $sql, array $params = []): PDOStatement => $this->fakeExecute($sql, $params),
        );

        $this->connection = $connection;
    }

    public function testAnEmptyQueueProducesAnEmptySummaryWithoutError(): void
    {
        $summary = $this->makeRunner()->run();

        self::assertSame(0, $summary->processedCount());
        self::assertSame(0, $summary->successCount());
        self::assertSame(0, $summary->failureCount());
    }

    public function testProcessesEveryQueuedJobAndReportsAnAccurateSummary(): void
    {
        $this->givenQueuedJob('valid_onet.xlsx', 'xlsx', assessmentId: 3);
        $this->givenQueuedJob('valid_onet.xlsx', 'xlsx', assessmentId: 4);

        $summary = $this->makeRunner()->run();

        self::assertSame(2, $summary->processedCount());
        self::assertSame(2, $summary->successCount());
        self::assertSame(0, $summary->failureCount());
        self::assertCount(6, $this->state['scores'], '3 rows committed per job, 2 jobs');
    }

    public function testProcessesQueuedJobsOldestFirst(): void
    {
        $first = $this->givenQueuedJob('valid_onet.xlsx', 'xlsx', assessmentId: 3);
        $second = $this->givenQueuedJob('valid_onet.xlsx', 'xlsx', assessmentId: 4);

        $this->makeRunner()->run();

        // Both committed, but the first-queued job's rows must appear before the second's.
        self::assertSame(
            $first,
            (int) $this->state['scores'][0][3],
            'first row committed belongs to the first-queued job',
        );
        self::assertSame(
            $second,
            (int) $this->state['scores'][3][3],
            'fourth row committed belongs to the second-queued job',
        );
    }

    public function testRespectsTheMaxJobsPerRunBound(): void
    {
        $this->givenQueuedJob('valid_onet.xlsx', 'xlsx', assessmentId: 3);
        $this->givenQueuedJob('valid_onet.xlsx', 'xlsx', assessmentId: 4);
        $this->givenQueuedJob('valid_onet.xlsx', 'xlsx', assessmentId: 3, filePath: 'other_path.xlsx');

        $summary = $this->makeRunner(maxJobsPerRun: 2)->run();

        self::assertSame(2, $summary->processedCount());
    }

    public function testAJobThatCannotBeProcessedIsIsolatedAndTheRunnerContinues(): void
    {
        $this->givenQueuedJob('valid_onet.xlsx', 'xlsx', assessmentId: 3);
        $this->givenQueuedJob('valid_onet.xlsx', 'xlsx', assessmentId: 4);

        // No template is registered under this key — every job's resolveForAssessment() throws.
        $registry = new TemplateRegistry();
        $resolver = new TemplateResolver($registry, 'NOT-REGISTERED');
        $runner = $this->makeRunnerWithResolver($resolver);

        $summary = $runner->run();

        self::assertSame(2, $summary->processedCount(), 'both queued jobs were attempted, not just the first');
        self::assertSame(0, $summary->successCount());
        self::assertSame(2, $summary->failureCount());

        foreach ($summary->outcomes as $outcome) {
            self::assertFalse($outcome->success);
            self::assertSame(['Import job could not be processed.'], $outcome->rowErrors);
        }

        foreach ($this->state['jobs'] as $job) {
            self::assertSame(
                'failed',
                $job['status'],
                'a job that never reached the pipeline is still marked failed, not left queued',
            );
        }

        self::assertSame([], $this->state['scores'], 'nothing was ever committed');
    }

    private function givenQueuedJob(
        string $fixtureFile,
        string $fileType,
        int $assessmentId,
        ?string $filePath = null,
    ): int {
        $jobId = count($this->state['jobs']) + 1;

        $this->state['jobs'][$jobId] = [
            'id' => $jobId,
            'school_id' => 1,
            'assessment_id' => $assessmentId,
            'file_path' => $filePath ?? (self::FIXTURES . '/' . $fixtureFile),
            'file_type' => $fileType,
            'status' => 'queued',
            'error_detail' => null,
            'uploaded_by' => 1,
        ];

        return $jobId;
    }

    private function makeRunner(int $maxJobsPerRun = 10): ImportJobRunner
    {
        $template = ExampleTemplates::studentIdAndScore();
        $registry = new TemplateRegistry();
        $registry->register($template);

        return $this->makeRunnerWithResolver(new TemplateResolver($registry, $template->key), $maxJobsPerRun);
    }

    private function makeRunnerWithResolver(TemplateResolver $resolver, int $maxJobsPerRun = 10): ImportJobRunner
    {
        $connection = $this->connection;

        $jobManager = new ImportJobManager(new ImportJobRepository($connection), new ImportLogRepository($connection));

        $scoreImportService = new ScoreImportService(
            new ImportJobRepository($connection),
            $jobManager,
            ['xlsx' => new ExcelParser(), 'csv' => new CsvParser()],
            new ColumnMapper(),
            new RowValidator(new TemplateValidator()),
            new StudentResolver(new StudentRepository($connection)),
            new AssessmentResolver(new AssessmentRepository($connection)),
            new ScoreNormalizer(),
            new ImportTransactionService($connection, new StudentScoreRepository($connection)),
            new DuplicateDetectionService(
                new StudentScoreRepository($connection),
                new ImportJobRepository($connection),
            ),
            new AuditTrailService(
                new ImportAuditLogger(new ImportLogRepository($connection)),
                new ImportLogRepository($connection),
                new ImportJobRepository($connection),
            ),
        );

        return new ImportJobRunner(
            $jobManager,
            new ImportSessionService($scoreImportService),
            $resolver,
            $maxJobsPerRun,
        );
    }

    /** @param array<int, mixed> $params */
    private function fakeExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->createMock(PDOStatement::class);

        if (str_contains($sql, 'FROM import_jobs') && str_contains($sql, 'ORDER BY created_at ASC, id ASC')
            && str_contains($sql, 'status = ?') && !str_contains($sql, 'school_id')
        ) {
            $rows = array_values(array_filter(
                $this->state['jobs'],
                static fn (array $job): bool => $job['status'] === 'queued',
            ));
            usort($rows, static fn (array $a, array $b): int => $a['id'] <=> $b['id']);
            $statement->method('fetchAll')->willReturn($rows);

            return $statement;
        }

        if (str_contains($sql, 'FROM import_jobs') && str_contains($sql, 'WHERE id = ?')) {
            $row = $this->state['jobs'][(int) $params[0]] ?? false;
            $statement->method('fetch')->willReturn($row);

            return $statement;
        }

        if (str_starts_with($sql, 'UPDATE import_jobs')) {
            $id = (int) end($params);

            if (str_contains($sql, 'error_detail')) {
                $this->state['jobs'][$id]['status'] = $params[0];
                $this->state['jobs'][$id]['error_detail'] = $params[1];
            } else {
                $this->state['jobs'][$id]['status'] = $params[0];
            }

            $statement->method('rowCount')->willReturn(1);

            return $statement;
        }

        if (str_starts_with($sql, 'INSERT INTO import_logs')) {
            $this->state['logs'][] = $params;
            $statement->method('rowCount')->willReturn(1);

            return $statement;
        }

        if (str_contains($sql, 'FROM assessments') && str_contains($sql, 'WHERE id = ?')) {
            $row = $this->state['assessments'][(int) $params[0]] ?? false;
            $statement->method('fetch')->willReturn($row);

            return $statement;
        }

        if (str_contains($sql, 'FROM students') && str_contains($sql, 'WHERE student_id = ?')) {
            $row = $this->state['students'][(string) $params[0]] ?? false;
            $statement->method('fetch')->willReturn($row);

            return $statement;
        }

        if (str_starts_with($sql, 'INSERT INTO student_scores')) {
            $this->state['scores'][] = $params;
            $statement->method('rowCount')->willReturn(1);

            return $statement;
        }

        if (str_contains($sql, 'FROM student_scores') && str_contains($sql, 'student_id = ?')) {
            $exists = false;

            foreach ($this->state['scores'] as $row) {
                if ($row[0] === (string) $params[0] && (int) $row[1] === (int) $params[1]) {
                    $exists = true;

                    break;
                }
            }

            $statement->method('fetch')->willReturn($exists ? [1] : false);

            return $statement;
        }

        if (str_contains($sql, 'FROM import_jobs') && str_contains($sql, 'file_path = ?')) {
            $row = false;

            foreach ($this->state['jobs'] as $job) {
                if (
                    (int) $job['school_id'] === (int) $params[0]
                    && (int) $job['assessment_id'] === (int) $params[1]
                    && $job['file_path'] === (string) $params[2]
                ) {
                    $row = $job;

                    break;
                }
            }

            $statement->method('fetch')->willReturn($row);

            return $statement;
        }

        if (str_contains($sql, 'FROM import_jobs') && str_contains($sql, 'status IN')) {
            $excludeId = $params[2] ?? null;
            $matches = array_values(array_filter(
                $this->state['jobs'],
                static fn (array $job): bool => (int) $job['school_id'] === (int) $params[0]
                    && (int) $job['assessment_id'] === (int) $params[1]
                    && in_array($job['status'], ['queued', 'processing'], true)
                    && ($excludeId === null || (int) $job['id'] !== (int) $excludeId),
            ));
            $statement->method('fetchAll')->willReturn($matches);

            return $statement;
        }

        throw new RuntimeException(sprintf('Unhandled SQL in ImportJobRunnerTest mock: %s', $sql));
    }
}
