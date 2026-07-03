<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\QuestionRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class QuestionRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('101');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('INSERT INTO questions'), [3, 1, 1, '1'])
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new QuestionRepository($connection);

        $id = $repository->create([
            'assessment_id' => 3,
            'item_number' => 1,
            'primary_indicator_id' => 1,
            'correct_choice' => '1',
        ]);

        self::assertSame(101, $id);
    }

    public function testUpdateBuildsAssignmentsAndScopesByIdWithoutAnUpdatedAtColumn(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(1);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('primary_indicator_id = ?'),
                    self::stringContains('WHERE id = ?'),
                    self::logicalNot(self::stringContains('updated_at')),
                ),
                [2, 101],
            )
            ->willReturn($statement);

        $repository = new QuestionRepository($connection);

        self::assertTrue($repository->update(101, ['primary_indicator_id' => 2]));
    }

    public function testDeleteScopesById(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(1);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE id = ?'), [101])
            ->willReturn($statement);

        $repository = new QuestionRepository($connection);

        self::assertTrue($repository->delete(101));
    }
}
