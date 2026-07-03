<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Session;

use DMF\Import\Audit\AuditTrailService;
use DMF\Import\Audit\DuplicateDetectionService;
use DMF\Import\Audit\ImportAuditLogger;
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
use DMF\Import\Session\RetryFailedImport;
use DMF\Import\Template\ColumnMapper;
use DMF\Import\Template\ExampleTemplates;
use DMF\Import\Template\TemplateValidator;
use DMF\Repository\AssessmentRepository;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\ImportLogRepository;
use DMF\Repository\StudentRepository;
use DMF\Repository\StudentScoreRepository;
use Dmf\Core\Contract\ConnectionInterface;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RetryFailedImportTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../../../fixtures/import';

    /** @var array<string, mixed> */
    private array $state;

    private ConnectionInterface $connection;

    protected function setUp(): void
    {
        $this->state = [
            'jobs' => [],
            'assessments' => [3 => ['id' => 3, 'subject_code' => 'MATH', 'academic_year' => 2569]],
            'students' => [
                'S001' => ['student_id' => 'S001', 'full_name' => 'Student One'],
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

    public function testRetryingAFixedJobSucceedsAfterTheUnderlyingConditionIsResolved(): void
    {
        // valid_onet.xlsx references S001-S003; S002 is missing from state, so the
        // first run fails on "No student found with student_id S002".
        $jobId = $this->givenQueuedJob('valid_onet.xlsx', 'xlsx');
        $this->makeSessionService()->run($jobId, ExampleTemplates::studentIdAndScore());

        self::assertSame('failed', $this->state['jobs'][$jobId]['status']);
        self::assertNotNull($this->state['jobs'][$jobId]['error_detail']);

        // The condition is now resolved — S002 exists.
        $this->state['students']['S002'] = ['student_id' => 'S002', 'full_name' => 'Student Two'];

        $result = $this->makeRetry()->execute($jobId, ExampleTemplates::studentIdAndScore());

        self::assertTrue($result->success);
        self::assertSame(3, $result->committedRows);
        self::assertSame('committed', $this->state['jobs'][$jobId]['status']);
        self::assertSame(
            [
                'import_started', 'parsed', 'mapped', 'rejected',
                'queued', 'retry',
                'import_started', 'parsed', 'mapped', 'validated', 'committed',
            ],
            array_column($this->state['logs'], 1),
        );
    }

    public function testRetryingAJobThatFailedOnADuplicateSucceedsOnceTheDuplicateIsResolved(): void
    {
        // S001 already has a committed score for assessment 3 — the first run is rejected as a
        // duplicate (FR-007), never reaching row validation/commit. S002 exists here (unlike the
        // other retry test in this file) so the retry's success is attributable to the duplicate
        // condition being resolved, not to an unrelated missing-student fix.
        $this->state['scores'][] = ['S001', 3, 88.0, 999];
        $this->state['students']['S002'] = ['student_id' => 'S002', 'full_name' => 'Student Two'];

        $jobId = $this->givenQueuedJob('valid_onet.xlsx', 'xlsx');
        $this->makeSessionService()->run($jobId, ExampleTemplates::studentIdAndScore());

        self::assertSame('failed', $this->state['jobs'][$jobId]['status']);
        self::assertStringContainsString('Duplicate detected', (string) $this->state['jobs'][$jobId]['error_detail']);
        self::assertSame(
            ['import_started', 'parsed', 'mapped', 'duplicate_found', 'rejected'],
            array_column($this->state['logs'], 1),
        );

        // The duplicate condition is resolved (e.g. the earlier, erroneous score is corrected away).
        $this->state['scores'] = [];

        $result = $this->makeRetry()->execute($jobId, ExampleTemplates::studentIdAndScore());

        self::assertTrue($result->success);
        self::assertSame(3, $result->committedRows);
        self::assertSame('committed', $this->state['jobs'][$jobId]['status']);
    }

    public function testRetryingAJobThatIsNotFailedIsRejected(): void
    {
        $jobId = $this->givenQueuedJob('valid_onet.xlsx', 'xlsx');

        $this->expectException(InvalidArgumentException::class);

        $this->makeRetry()->execute($jobId, ExampleTemplates::studentIdAndScore());
    }

    public function testRetryingAnUnknownJobThrows(): void
    {
        $this->expectException(RuntimeException::class);

        $this->makeRetry()->execute(999, ExampleTemplates::studentIdAndScore());
    }

    private function givenQueuedJob(string $fixtureFile, string $fileType): int
    {
        $jobId = count($this->state['jobs']) + 1;

        $this->state['jobs'][$jobId] = [
            'id' => $jobId,
            'school_id' => 1,
            'assessment_id' => 3,
            'file_path' => self::FIXTURES . '/' . $fixtureFile,
            'file_type' => $fileType,
            'status' => 'queued',
            'error_detail' => null,
            'uploaded_by' => 1,
        ];

        return $jobId;
    }

    private function makeSessionService(): ImportSessionService
    {
        $connection = $this->connection;

        $scoreImportService = new ScoreImportService(
            new ImportJobRepository($connection),
            new ImportJobManager(new ImportJobRepository($connection), new ImportLogRepository($connection)),
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
            $this->makeAuditTrail(),
        );

        return new ImportSessionService($scoreImportService);
    }

    private function makeRetry(): RetryFailedImport
    {
        $connection = $this->connection;

        return new RetryFailedImport(
            new ImportJobRepository($connection),
            new ImportJobManager(new ImportJobRepository($connection), new ImportLogRepository($connection)),
            $this->makeSessionService(),
            $this->makeAuditTrail(),
        );
    }

    private function makeAuditTrail(): AuditTrailService
    {
        $connection = $this->connection;

        return new AuditTrailService(
            new ImportAuditLogger(new ImportLogRepository($connection)),
            new ImportLogRepository($connection),
            new ImportJobRepository($connection),
        );
    }

    /** @param array<int, mixed> $params */
    private function fakeExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->createMock(PDOStatement::class);

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

        throw new RuntimeException(sprintf('Unhandled SQL in RetryFailedImportTest mock: %s', $sql));
    }
}
