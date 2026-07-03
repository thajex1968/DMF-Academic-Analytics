<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\StaffUserRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class StaffUserRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('7');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::stringContains('INSERT INTO staff_users'),
                ['teacher01', 'hash', 'Teacher One', 'teacher', 1, 1],
            )
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new StaffUserRepository($connection);

        $id = $repository->create([
            'username' => 'teacher01',
            'password_hash' => 'hash',
            'display_name' => 'Teacher One',
            'role' => 'teacher',
            'school_id' => 1,
        ]);

        self::assertSame(7, $id);
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
                    self::stringContains('display_name = ?'),
                    self::stringContains('WHERE id = ?'),
                ),
                ['New Name', 7],
            )
            ->willReturn($statement);

        $repository = new StaffUserRepository($connection);

        self::assertTrue($repository->update(7, ['display_name' => 'New Name']));
    }

    public function testDeleteSoftDeletesRatherThanHardDeleting(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(1);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('UPDATE staff_users'),
                    self::stringContains('deleted_at = NOW()'),
                    self::logicalNot(self::stringContains('DELETE FROM')),
                ),
                [7],
            )
            ->willReturn($statement);

        $repository = new StaffUserRepository($connection);

        self::assertTrue($repository->delete(7));
    }

    public function testFindByUsernameReturnsTheMatchingRow(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(['id' => 7, 'username' => 'teacher01']);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::anything(), ['teacher01'])
            ->willReturn($statement);

        $repository = new StaffUserRepository($connection);

        self::assertSame(['id' => 7, 'username' => 'teacher01'], $repository->findByUsername('teacher01'));
    }

    public function testFindByUsernameReturnsNullWhenNothingMatches(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(false);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturn($statement);

        $repository = new StaffUserRepository($connection);

        self::assertNull($repository->findByUsername('nobody'));
    }
}
