<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import;

use DMF\Import\ImportJobManager;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\ImportLogRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * ImportJobRepository/ImportLogRepository are `final` and cannot be mocked
 * directly (PHPUnit\Framework\MockObject\Generator\ClassIsFinalException) —
 * these tests exercise ImportJobManager against real repository instances
 * wrapping a mocked ConnectionInterface instead, one level deeper than
 * mocking the repositories themselves, verifying the same outcome.
 */
final class ImportJobManagerTest extends TestCase
{
    /** @var list<array{0: string, 1: array<int, mixed>}> */
    private array $executedQueries = [];

    private ImportJobManager $manager;

    protected function setUp(): void
    {
        $this->executedQueries = [];
        $queries = &$this->executedQueries;

        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('10');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('pdo')->willReturn($pdo);
        $connection->method('execute')->willReturnCallback(
            function (string $sql, array $params = []) use (&$queries): PDOStatement {
                $queries[] = [$sql, $params];

                $statement = $this->createMock(PDOStatement::class);
                $statement->method('rowCount')->willReturn(1);

                return $statement;
            },
        );

        $this->manager = new ImportJobManager(
            new ImportJobRepository($connection),
            new ImportLogRepository($connection),
        );
    }

    public function testCreateQueuedJobRegistersTheJobAndLogsTheQueuedEvent(): void
    {
        $jobId = $this->manager->createQueuedJob([
            'school_id' => 1,
            'assessment_id' => 1,
            'file_path' => 'storage/imports/x.xlsx',
            'file_type' => 'xlsx',
            'uploaded_by' => 2,
        ]);

        self::assertSame(10, $jobId);
        self::assertCount(2, $this->executedQueries);

        [$jobSql, $jobParams] = $this->executedQueries[0];
        self::assertStringContainsString('INSERT INTO import_jobs', $jobSql);
        self::assertSame([1, 1, 'storage/imports/x.xlsx', 'xlsx', 'queued', 2], $jobParams);

        [$logSql, $logParams] = $this->executedQueries[1];
        self::assertStringContainsString('INSERT INTO import_logs', $logSql);
        self::assertSame([10, 'queued', null, 2], $logParams);
    }

    public function testMarkProcessingUpdatesStatusOnlyAndDoesNotLog(): void
    {
        // "processing" is not a valid import_logs.event value (docs/Data-Dictionary.md §5:
        // "One of queued, parsed, validated, mapped, committed, rejected") — only the
        // import_jobs.status column records this transition.
        $this->manager->markProcessing(10);

        self::assertCount(1, $this->executedQueries);

        [$updateSql, $updateParams] = $this->executedQueries[0];
        self::assertStringContainsString('UPDATE import_jobs SET status = ?', $updateSql);
        self::assertSame(['processing', 10], $updateParams);
    }

    public function testMarkCommittedUpdatesStatusAndLogs(): void
    {
        $this->manager->markCommitted(10);

        [$updateSql, $updateParams] = $this->executedQueries[0];
        self::assertStringContainsString('status = ?', $updateSql);
        self::assertSame(['committed', 10], $updateParams);

        [, $logParams] = $this->executedQueries[1];
        self::assertSame([10, 'committed', null, null], $logParams);
    }

    public function testMarkFailedRecordsTheErrorDetailAndLogsRejected(): void
    {
        $this->manager->markFailed(10, 'Row 5: score out of range');

        [$updateSql, $updateParams] = $this->executedQueries[0];
        self::assertStringContainsString('status = ?', $updateSql);
        self::assertStringContainsString('error_detail = ?', $updateSql);
        self::assertSame(['failed', 'Row 5: score out of range', 10], $updateParams);

        [, $logParams] = $this->executedQueries[1];
        self::assertSame([10, 'rejected', 'Row 5: score out of range', null], $logParams);
    }

    public function testLogWritesAFreeFormEntry(): void
    {
        $this->manager->log(10, 'mapped', 'all headers resolved');

        [$logSql, $logParams] = $this->executedQueries[0];
        self::assertStringContainsString('INSERT INTO import_logs', $logSql);
        self::assertSame([10, 'mapped', 'all headers resolved', null], $logParams);
    }

    public function testHistoryDelegatesToImportLogRepository(): void
    {
        // rowCount() isn't relevant here; override execute() to return fetchAll() data instead.
        $connection = $this->createMock(ConnectionInterface::class);
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['event' => 'queued']]);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE import_job_id = ?'), [10])
            ->willReturn($statement);

        $manager = new ImportJobManager(new ImportJobRepository($connection), new ImportLogRepository($connection));

        self::assertSame([['event' => 'queued']], $manager->history(10));
    }

    public function testQueuedJobsDelegatesToImportJobRepository(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['id' => 10, 'status' => 'queued']]);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE status = ?'), ['queued'])
            ->willReturn($statement);

        $manager = new ImportJobManager(new ImportJobRepository($connection), new ImportLogRepository($connection));

        self::assertSame([['id' => 10, 'status' => 'queued']], $manager->queuedJobs());
    }
}
