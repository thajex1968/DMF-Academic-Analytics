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
use DMF\Import\Template\ColumnMapper;
use DMF\Import\Template\ExampleTemplates;
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
 * ImportSessionService is a thin facade over the real ScoreImportService
 * (T2.3) — driven end to end through the Golden Test Dataset, same as
 * ScoreImportServiceTest, to prove run()/summarize()/buildErrorReport()
 * compose correctly on top of a real ImportResult rather than a stub.
 */
final class ImportSessionServiceTest extends TestCase
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

    public function testRunAndSummarizeReportACommittedImport(): void
    {
        $jobId = $this->givenQueuedJob('valid_onet.xlsx', 'xlsx');
        $service = $this->makeSessionService();

        $result = $service->run($jobId, ExampleTemplates::studentIdAndScore());
        $summary = $service->summarize($result);

        self::assertTrue($result->success);
        self::assertSame('committed', $summary->status);
        self::assertSame(3, $summary->committedRows);
        self::assertSame(0, $summary->rejectedRows);
    }

    public function testRunAndSummarizeReportAFailedImportAndBuildErrorReportIsTraceable(): void
    {
        $jobId = $this->givenQueuedJob('invalid_score.xlsx', 'xlsx');
        $service = $this->makeSessionService();

        $result = $service->run($jobId, ExampleTemplates::studentIdAndScore());
        $summary = $service->summarize($result);
        $report = $service->buildErrorReport($result);

        self::assertFalse($result->success);
        self::assertSame('failed', $summary->status);
        self::assertSame($jobId, $report->importJobId);
        self::assertNotEmpty($report->rowErrors);

        foreach ($report->rowErrors as $rowError) {
            self::assertGreaterThan(0, $rowError->rowNumber, 'every row-level error must be traceable to a row');
        }
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
            new AuditTrailService(
                new ImportAuditLogger(new ImportLogRepository($connection)),
                new ImportLogRepository($connection),
                new ImportJobRepository($connection),
            ),
        );

        return new ImportSessionService($scoreImportService);
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

        throw new RuntimeException(sprintf('Unhandled SQL in ImportSessionServiceTest mock: %s', $sql));
    }
}
