<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\LearningStandardRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class LearningStandardRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('1');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('INSERT INTO learning_standards'), [1, 'ค1.1', 'มาตรฐานทดสอบ 1'])
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new LearningStandardRepository($connection);

        $id = $repository->create([
            'strand_id' => 1,
            'standard_code' => 'ค1.1',
            'standard_name_th' => 'มาตรฐานทดสอบ 1',
        ]);

        self::assertSame(1, $id);
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

        $repository = new LearningStandardRepository($connection);

        self::assertTrue($repository->delete(1));
    }
}
