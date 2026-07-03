<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\LoginRateLimitRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class LoginRateLimitRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('3');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('INSERT INTO login_rate_limits'), ['teacher01', 1, null])
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new LoginRateLimitRepository($connection);

        $id = $repository->create(['username' => 'teacher01', 'failed_attempts' => 1]);

        self::assertSame(3, $id);
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
                    self::stringContains('failed_attempts = ?'),
                    self::stringContains('WHERE id = ?'),
                ),
                [2, 3],
            )
            ->willReturn($statement);

        $repository = new LoginRateLimitRepository($connection);

        self::assertTrue($repository->update(3, ['failed_attempts' => 2]));
    }

    public function testFindByUsernameReturnsTheMatchingRow(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(['id' => 3, 'username' => 'teacher01', 'failed_attempts' => 2]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::anything(), ['teacher01'])
            ->willReturn($statement);

        $repository = new LoginRateLimitRepository($connection);

        self::assertSame(
            ['id' => 3, 'username' => 'teacher01', 'failed_attempts' => 2],
            $repository->findByUsername('teacher01'),
        );
    }

    public function testFindByUsernameReturnsNullWhenNothingMatches(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(false);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturn($statement);

        $repository = new LoginRateLimitRepository($connection);

        self::assertNull($repository->findByUsername('nobody'));
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

        $repository = new LoginRateLimitRepository($connection);

        self::assertTrue($repository->delete(3));
    }
}
