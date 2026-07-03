<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Score;

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
 * End-to-end: real ScoreImportService, real ExcelParser/CsvParser, real
 * ColumnMapper, real TemplateValidator/RowValidator, real repositories —
 * all over one stateful mocked ConnectionInterface standing in for the
 * database, driven by the Golden Test Dataset (tests/fixtures/import/).
 */
final class ScoreImportServiceTest extends TestCase
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

    public function testValidXlsxFileCommitsAllRows(): void
    {
        $jobId = $this->givenQueuedJob('valid_onet.xlsx', 'xlsx');

        $result = $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        self::assertTrue($result->success);
        self::assertSame(3, $result->committedRows);
        self::assertSame([], $result->rowErrors);
        self::assertSame('committed', $this->state['jobs'][$jobId]['status']);
        self::assertCount(3, $this->state['scores']);
    }

    public function testValidCsvFileCommitsAllRows(): void
    {
        $jobId = $this->givenQueuedJob('valid_onet.csv', 'csv');

        $result = $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        self::assertTrue($result->success);
        self::assertSame(3, $result->committedRows);
    }

    public function testMissingStudentIdRejectsTheWholeJobWithNoPartialCommit(): void
    {
        $jobId = $this->givenQueuedJob('missing_student_id.xlsx', 'xlsx');

        $result = $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        self::assertFalse($result->success);
        self::assertSame(0, $result->committedRows);
        self::assertSame('failed', $this->state['jobs'][$jobId]['status']);
        self::assertSame([], $this->state['scores'], 'no partial commit — zero rows should be written');
        self::assertNotEmpty($result->rowErrors);
    }

    public function testDuplicateStudentWithinTheFileRejectsTheWholeJob(): void
    {
        $jobId = $this->givenQueuedJob('duplicate_student.xlsx', 'xlsx');

        $result = $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        self::assertFalse($result->success);
        self::assertSame([], $this->state['scores']);
        self::assertStringContainsString('Duplicate student_id', implode(' ', $result->rowErrors));
    }

    public function testInvalidScoreRejectsTheWholeJob(): void
    {
        $jobId = $this->givenQueuedJob('invalid_score.xlsx', 'xlsx');

        $result = $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        self::assertFalse($result->success);
        self::assertSame([], $this->state['scores']);
        $joined = implode(' ', $result->rowErrors);
        self::assertStringContainsString('0.00–100.00', $joined);
        self::assertStringContainsString('not a valid numeric score', $joined);
    }

    public function testMissingRequiredColumnRejectsEveryRow(): void
    {
        $jobId = $this->givenQueuedJob('missing_required_column.xlsx', 'xlsx');

        $result = $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        self::assertFalse($result->success);
        self::assertSame([], $this->state['scores']);
        self::assertCount(2, $result->rowErrors, 'both rows lack the required score column');
    }

    public function testBlankRowsAreSkippedAndTheRemainingValidRowsCommit(): void
    {
        $jobId = $this->givenQueuedJob('blank_rows.xlsx', 'xlsx');

        $result = $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        self::assertTrue($result->success);
        self::assertSame(2, $result->committedRows);
    }

    public function testUtf8CsvCommitsCorrectly(): void
    {
        $jobId = $this->givenQueuedJob('utf8.csv', 'csv');

        $result = $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        self::assertTrue($result->success);
        self::assertSame(3, $result->committedRows);
    }

    public function testTis620CsvCommitsCorrectly(): void
    {
        $jobId = $this->givenQueuedJob('tis620.csv', 'csv');

        $result = $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        self::assertTrue($result->success);
        self::assertSame(3, $result->committedRows);
    }

    public function testAnUnresolvableStudentIdRejectsTheWholeJob(): void
    {
        // valid_onet.xlsx's students are S001-S003, all seeded in setUp() — remove one
        // so it becomes "unknown" to StudentResolver.
        unset($this->state['students']['S002']);

        $jobId = $this->givenQueuedJob('valid_onet.xlsx', 'xlsx');

        $result = $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        self::assertFalse($result->success);
        self::assertStringContainsString('No student found with student_id "S002"', implode(' ', $result->rowErrors));
    }

    public function testPipelineStagesAreLoggedInOrder(): void
    {
        $jobId = $this->givenQueuedJob('valid_onet.xlsx', 'xlsx');

        $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        $events = array_column($this->state['logs'], 1);
        self::assertSame(['import_started', 'parsed', 'mapped', 'validated', 'committed'], $events);
    }

    public function testAStudentWithAnAlreadyCommittedScoreForTheSameAssessmentRejectsTheWholeJob(): void
    {
        // S001 already has a committed score for assessment 3 from an earlier import (job 999).
        $this->state['scores'][] = ['S001', 3, 90.0, 999];

        $jobId = $this->givenQueuedJob('valid_onet.xlsx', 'xlsx');

        $result = $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        self::assertFalse($result->success);
        self::assertSame(
            [],
            array_slice($this->state['scores'], 1),
            'no new rows committed — only the pre-seeded row remains',
        );
        self::assertStringContainsString(
            'Duplicate detected: 1 student(s) already have a committed score for this assessment.',
            implode(' ', $result->rowErrors),
        );
        self::assertSame(
            ['import_started', 'parsed', 'mapped', 'duplicate_found', 'rejected'],
            array_column($this->state['logs'], 1),
        );
    }

    public function testAnActiveJobForTheSameSchoolAndAssessmentRejectsANewImport(): void
    {
        // Another job for the same school+assessment is already queued.
        $this->state['jobs'][100] = [
            'id' => 100,
            'school_id' => 1,
            'assessment_id' => 3,
            'file_path' => self::FIXTURES . '/other_file.xlsx',
            'file_type' => 'xlsx',
            'status' => 'queued',
            'error_detail' => null,
            'uploaded_by' => 1,
        ];

        $jobId = $this->givenQueuedJob('valid_onet.xlsx', 'xlsx');

        $result = $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        self::assertFalse($result->success);
        self::assertSame([], $this->state['scores']);
        self::assertStringContainsString(
            sprintf('Another import job is already active for this assessment (job id(s): %d).', 100),
            implode(' ', $result->rowErrors),
        );
    }

    public function testDuplicateFoundIsAuditedWithASafeStructuredMessageNotARawException(): void
    {
        $this->state['scores'][] = ['S001', 3, 90.0, 999];

        $jobId = $this->givenQueuedJob('valid_onet.xlsx', 'xlsx');

        $this->makeService()->import($jobId, ExampleTemplates::studentIdAndScore());

        $duplicateLog = null;

        foreach ($this->state['logs'] as $log) {
            if ($log[1] === 'duplicate_found') {
                $duplicateLog = $log;
            }
        }

        self::assertNotNull($duplicateLog, 'a duplicate_found event must be logged');
        // params: [import_job_id, event, message, actor_id]
        self::assertStringNotContainsString('SQLSTATE', (string) $duplicateLog[2]);
        self::assertStringNotContainsString('PDOException', (string) $duplicateLog[2]);
        self::assertStringNotContainsString('Stack trace', (string) $duplicateLog[2]);
        self::assertStringContainsString('Duplicate detected', (string) $duplicateLog[2]);
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

    private function makeService(): ScoreImportService
    {
        $connection = $this->connection;

        return new ScoreImportService(
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

        throw new RuntimeException(sprintf('Unhandled SQL in ScoreImportServiceTest mock: %s', $sql));
    }
}
