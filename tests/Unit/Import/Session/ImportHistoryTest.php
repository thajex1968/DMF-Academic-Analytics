<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Session;

use DMF\Import\ImportJobManager;
use DMF\Import\Session\ImportHistory;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\ImportLogRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * ImportJobRepository/ImportLogRepository are `final` — ImportHistory is
 * exercised through real instances of both over a mocked ConnectionInterface,
 * same pattern as ImportJobManagerTest.
 */
final class ImportHistoryTest extends TestCase
{
    public function testForSchoolDelegatesToImportJobRepository(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['id' => 10, 'school_id' => 1, 'status' => 'committed']]);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE school_id = ?'), [1])
            ->willReturn($statement);

        $history = new ImportHistory(
            new ImportJobRepository($connection),
            new ImportJobManager(new ImportJobRepository($connection), new ImportLogRepository($connection)),
        );

        self::assertSame([['id' => 10, 'school_id' => 1, 'status' => 'committed']], $history->forSchool(1));
    }

    public function testTimelineDelegatesToImportJobManagerHistory(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['event' => 'queued'], ['event' => 'committed']]);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE import_job_id = ?'), [10])
            ->willReturn($statement);

        $history = new ImportHistory(
            new ImportJobRepository($connection),
            new ImportJobManager(new ImportJobRepository($connection), new ImportLogRepository($connection)),
        );

        self::assertSame([['event' => 'queued'], ['event' => 'committed']], $history->timeline(10));
    }

    public function testErrorReportForAFailedJobParsesThePersistedErrorDetail(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn([
            'id' => 10,
            'status' => 'failed',
            'error_detail' => 'Row 2: bad score | Row 4: unknown student_id',
        ]);
        $connection->method('execute')->willReturn($statement);

        $history = new ImportHistory(
            new ImportJobRepository($connection),
            new ImportJobManager(new ImportJobRepository($connection), new ImportLogRepository($connection)),
        );

        $report = $history->errorReportFor(10);

        self::assertNotNull($report);
        self::assertSame(10, $report->importJobId);
        self::assertCount(2, $report->rowErrors);
        self::assertSame(2, $report->rowErrors[0]->rowNumber);
        self::assertSame(4, $report->rowErrors[1]->rowNumber);
    }

    public function testErrorReportForReturnsNullWhenTheJobIsNotFailed(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(['id' => 10, 'status' => 'committed', 'error_detail' => null]);
        $connection->method('execute')->willReturn($statement);

        $history = new ImportHistory(
            new ImportJobRepository($connection),
            new ImportJobManager(new ImportJobRepository($connection), new ImportLogRepository($connection)),
        );

        self::assertNull($history->errorReportFor(10));
    }

    public function testErrorReportForReturnsNullWhenTheJobDoesNotExist(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(false);
        $connection->method('execute')->willReturn($statement);

        $history = new ImportHistory(
            new ImportJobRepository($connection),
            new ImportJobManager(new ImportJobRepository($connection), new ImportLogRepository($connection)),
        );

        self::assertNull($history->errorReportFor(10));
    }
}
