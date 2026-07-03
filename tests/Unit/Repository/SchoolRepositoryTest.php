<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\SchoolRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class SchoolRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('1');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::stringContains('INSERT INTO schools'),
                ['47010005', 'โรงเรียนชุมชนดงมะไฟเจริญศิลป์', 'Sakon Nakhon'],
            )
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new SchoolRepository($connection);

        $id = $repository->create([
            'school_code' => '47010005',
            'name_th' => 'โรงเรียนชุมชนดงมะไฟเจริญศิลป์',
            'province' => 'Sakon Nakhon',
        ]);

        self::assertSame(1, $id);
    }

    public function testFindByIdReturnsTheMatchingRow(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(['id' => 1, 'name_th' => 'โรงเรียนชุมชนดงมะไฟเจริญศิลป์']);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturn($statement);

        $repository = new SchoolRepository($connection);

        self::assertSame(['id' => 1, 'name_th' => 'โรงเรียนชุมชนดงมะไฟเจริญศิลป์'], $repository->findById(1));
    }

    public function testDeleteScopesById(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(1);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE id = ?'), [1])
            ->willReturn($statement);

        $repository = new SchoolRepository($connection);

        self::assertTrue($repository->delete(1));
    }
}
