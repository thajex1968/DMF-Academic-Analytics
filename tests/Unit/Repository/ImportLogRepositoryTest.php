<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\ImportLogRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class ImportLogRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('5');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('INSERT INTO import_logs'), [9, 'queued', null, 2])
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new ImportLogRepository($connection);

        $id = $repository->create(['import_job_id' => 9, 'event' => 'queued', 'actor_id' => 2]);

        self::assertSame(5, $id);
    }

    public function testCreateDefaultsMessageAndActorIdToNull(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('6');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::anything(), [9, 'processing', null, null])
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new ImportLogRepository($connection);

        $repository->create(['import_job_id' => 9, 'event' => 'processing']);
    }

    public function testFindByImportJobOrdersByCreatedAtThenId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([
            ['id' => 1, 'event' => 'queued'],
            ['id' => 2, 'event' => 'processing'],
        ]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('WHERE import_job_id = ?'),
                    self::stringContains('ORDER BY created_at ASC, id ASC'),
                ),
                [9],
            )
            ->willReturn($statement);

        $repository = new ImportLogRepository($connection);

        self::assertSame(
            [['id' => 1, 'event' => 'queued'], ['id' => 2, 'event' => 'processing']],
            $repository->findByImportJob(9),
        );
    }
}
