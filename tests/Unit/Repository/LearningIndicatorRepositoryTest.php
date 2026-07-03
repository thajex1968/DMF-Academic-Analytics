<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\LearningIndicatorRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class LearningIndicatorRepositoryTest extends TestCase
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
                self::stringContains('INSERT INTO learning_indicators'),
                [1, 'ค1.1 ป.6/1', 'ตัวชี้วัดทดสอบ 1', 6, '2560'],
            )
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new LearningIndicatorRepository($connection);

        $id = $repository->create([
            'standard_id' => 1,
            'indicator_code' => 'ค1.1 ป.6/1',
            'indicator_name_th' => 'ตัวชี้วัดทดสอบ 1',
            'grade_level' => 6,
            'curriculum_revision' => '2560',
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

        $repository = new LearningIndicatorRepository($connection);

        self::assertTrue($repository->delete(1));
    }
}
