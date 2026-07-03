<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\LearningStrandRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class LearningStrandRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('1');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('INSERT INTO learning_strands'), ['MATH', 'ค1', 'จำนวนและพีชคณิต (ทดสอบ)'])
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new LearningStrandRepository($connection);

        $id = $repository->create([
            'subject_code' => 'MATH',
            'strand_code' => 'ค1',
            'strand_name_th' => 'จำนวนและพีชคณิต (ทดสอบ)',
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

        $repository = new LearningStrandRepository($connection);

        self::assertTrue($repository->delete(1));
    }
}
