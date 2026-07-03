<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\ClassroomRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class ClassroomRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('42');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('INSERT INTO classrooms'), [1, 6, 'ป.6/1', 2569])
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new ClassroomRepository($connection);

        $id = $repository->create([
            'school_id' => 1,
            'grade_level' => 6,
            'room_label' => 'ป.6/1',
            'academic_year' => 2569,
        ]);

        self::assertSame(42, $id);
    }

    public function testUpdateBuildsAssignmentsAndScopesById(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(1);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('room_label = ?'),
                    self::stringContains('updated_at = NOW()'),
                    self::stringContains('WHERE id = ?'),
                ),
                ['ป.6/2', 1],
            )
            ->willReturn($statement);

        $repository = new ClassroomRepository($connection);

        self::assertTrue($repository->update(1, ['room_label' => 'ป.6/2']));
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

        $repository = new ClassroomRepository($connection);

        self::assertTrue($repository->delete(1));
    }

    public function testFindBySchoolAndYearFiltersOnBoth(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['id' => 1, 'room_label' => 'ป.6/1']]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('WHERE school_id = ?'),
                    self::stringContains('AND academic_year = ?'),
                ),
                [1, 2569],
            )
            ->willReturn($statement);

        $repository = new ClassroomRepository($connection);

        self::assertSame([['id' => 1, 'room_label' => 'ป.6/1']], $repository->findBySchoolAndYear(1, 2569));
    }
}
