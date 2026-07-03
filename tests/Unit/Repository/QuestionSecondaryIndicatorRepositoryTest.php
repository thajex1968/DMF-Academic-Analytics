<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\QuestionSecondaryIndicatorRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class QuestionSecondaryIndicatorRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('1');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('INSERT INTO question_secondary_indicators'), [102, 2])
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new QuestionSecondaryIndicatorRepository($connection);

        self::assertSame(1, $repository->create(['question_id' => 102, 'indicator_id' => 2]));
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

        $repository = new QuestionSecondaryIndicatorRepository($connection);

        self::assertTrue($repository->delete(1));
    }

    public function testFindByQuestionScopesByQuestionId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['id' => 1, 'question_id' => 102, 'indicator_id' => 2]]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE question_id = ?'), [102])
            ->willReturn($statement);

        $repository = new QuestionSecondaryIndicatorRepository($connection);

        self::assertSame([['id' => 1, 'question_id' => 102, 'indicator_id' => 2]], $repository->findByQuestion(102));
    }
}
