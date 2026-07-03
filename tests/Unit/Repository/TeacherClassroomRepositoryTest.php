<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\TeacherClassroomRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class TeacherClassroomRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('3');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('INSERT INTO teacher_classrooms'), [2, 5, 1])
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new TeacherClassroomRepository($connection);

        $id = $repository->create(['staff_user_id' => 2, 'classroom_id' => 5]);

        self::assertSame(3, $id);
    }

    public function testCreateDefaultsIsCurrentToOne(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('4');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::anything(), [2, 5, 1])
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new TeacherClassroomRepository($connection);

        $repository->create(['staff_user_id' => 2, 'classroom_id' => 5, 'is_current' => 1]);
    }

    public function testUpdateDoesNotSetAnUpdatedAtColumn(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(1);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('is_current = ?'),
                    self::stringContains('WHERE id = ?'),
                    self::logicalNot(self::stringContains('updated_at')),
                ),
                [0, 3],
            )
            ->willReturn($statement);

        $repository = new TeacherClassroomRepository($connection);

        self::assertTrue($repository->update(3, ['is_current' => 0]));
    }

    public function testDeleteScopesById(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(1);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE id = ?'), [3])
            ->willReturn($statement);

        $repository = new TeacherClassroomRepository($connection);

        self::assertTrue($repository->delete(3));
    }

    public function testFindByStaffUserDelegatesToFindWhere(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['id' => 3, 'staff_user_id' => 2]]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE staff_user_id = ?'), [2])
            ->willReturn($statement);

        $repository = new TeacherClassroomRepository($connection);

        self::assertSame([['id' => 3, 'staff_user_id' => 2]], $repository->findByStaffUser(2));
    }

    public function testFindCurrentByStaffUserFiltersOnIsCurrent(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['id' => 3, 'is_current' => 1]]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('WHERE staff_user_id = ?'),
                    self::stringContains('AND is_current = 1'),
                ),
                [2],
            )
            ->willReturn($statement);

        $repository = new TeacherClassroomRepository($connection);

        self::assertSame([['id' => 3, 'is_current' => 1]], $repository->findCurrentByStaffUser(2));
    }

    public function testClearCurrentForStaffUserReturnsRowsAffected(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(2);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('SET is_current = 0'),
                    self::stringContains('WHERE staff_user_id = ?'),
                    self::stringContains('AND is_current = 1'),
                ),
                [2],
            )
            ->willReturn($statement);

        $repository = new TeacherClassroomRepository($connection);

        self::assertSame(2, $repository->clearCurrentForStaffUser(2));
    }
}
