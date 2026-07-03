<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\StudentRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class StudentRepositoryTest extends TestCase
{
    public function testCreateInsertsAllColumnsAndReturnsTheStudentId(): void
    {
        $statement = $this->createMock(PDOStatement::class);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::stringContains('INSERT INTO students'),
                ['S001', 5, 'Somchai Test', '1234567890123', 'active'],
            )
            ->willReturn($statement);

        $repository = new StudentRepository($connection);

        $id = $repository->create([
            'student_id' => 'S001',
            'classroom_id' => 5,
            'full_name' => 'Somchai Test',
            'national_id' => '1234567890123',
            'status' => 'active',
        ]);

        self::assertSame('S001', $id);
    }

    public function testCreateDefaultsStatusToActiveAndNationalIdToNull(): void
    {
        $statement = $this->createMock(PDOStatement::class);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::anything(), ['S002', 5, 'No Optional Fields', null, 'active'])
            ->willReturn($statement);

        $repository = new StudentRepository($connection);

        $repository->create([
            'student_id' => 'S002',
            'classroom_id' => 5,
            'full_name' => 'No Optional Fields',
        ]);
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
                    self::stringContains('full_name = ?'),
                    self::stringContains('updated_at = NOW()'),
                    self::stringContains('WHERE student_id = ?'),
                ),
                ['New Name', 'S001'],
            )
            ->willReturn($statement);

        $repository = new StudentRepository($connection);

        self::assertTrue($repository->update('S001', ['full_name' => 'New Name']));
    }

    public function testUpdateReturnsFalseWhenNoRowMatched(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(0);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturn($statement);

        $repository = new StudentRepository($connection);

        self::assertFalse($repository->update('missing', ['full_name' => 'x']));
    }

    public function testDeleteScopesByStudentId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(1);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE student_id = ?'), ['S001'])
            ->willReturn($statement);

        $repository = new StudentRepository($connection);

        self::assertTrue($repository->delete('S001'));
    }

    public function testFindByClassroomDelegatesToFindWhere(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['student_id' => 'S001']]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE classroom_id = ?'), [5])
            ->willReturn($statement);

        $repository = new StudentRepository($connection);

        self::assertSame([['student_id' => 'S001']], $repository->findByClassroom(5));
    }
}
