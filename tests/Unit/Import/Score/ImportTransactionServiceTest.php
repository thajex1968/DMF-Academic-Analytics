<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Score;

use DMF\Import\Score\ImportTransactionService;
use DMF\Repository\StudentScoreRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** StudentScoreRepository is `final` — used as a real instance over a mocked ConnectionInterface. */
final class ImportTransactionServiceTest extends TestCase
{
    public function testCommitsEveryRowInsideOneRealTransaction(): void
    {
        $insertedRows = [];
        $statement = $this->createMock(PDOStatement::class);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('1');
        $pdo->expects(self::once())->method('beginTransaction')->willReturn(true);
        $pdo->expects(self::once())->method('commit')->willReturn(true);
        $pdo->expects(self::never())->method('rollBack');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('pdo')->willReturn($pdo);
        $connection->method('execute')->willReturnCallback(
            function (string $sql, array $params = []) use (&$insertedRows, $statement): PDOStatement {
                $insertedRows[] = $params;

                return $statement;
            },
        );
        $connection->method('transaction')->willReturnCallback(function (callable $callback) use ($pdo) {
            $pdo->beginTransaction();

            try {
                $result = $callback();
                $pdo->commit();

                return $result;
            } catch (\Throwable $e) {
                $pdo->rollBack();

                throw $e;
            }
        });

        $service = new ImportTransactionService($connection, new StudentScoreRepository($connection));

        $committed = $service->commit([
            ['student_id' => 'S001', 'assessment_id' => 3, 'score' => 87.5, 'import_job_id' => 9],
            ['student_id' => 'S002', 'assessment_id' => 3, 'score' => 92.0, 'import_job_id' => 9],
        ]);

        self::assertSame(2, $committed);
        self::assertCount(2, $insertedRows);
        self::assertSame(['S001', 3, 87.5, 9], $insertedRows[0]);
        self::assertSame(['S002', 3, 92.0, 9], $insertedRows[1]);
    }

    public function testRollsBackAndRethrowsWhenAnInsertFails(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::once())->method('beginTransaction')->willReturn(true);
        $pdo->expects(self::never())->method('commit');
        $pdo->expects(self::once())->method('rollBack')->willReturn(true);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('pdo')->willReturn($pdo);
        $connection->method('execute')->willThrowException(new RuntimeException('duplicate key'));
        $connection->method('transaction')->willReturnCallback(function (callable $callback) use ($pdo) {
            $pdo->beginTransaction();

            try {
                return $callback();
            } catch (\Throwable $e) {
                $pdo->rollBack();

                throw $e;
            }
        });

        $service = new ImportTransactionService($connection, new StudentScoreRepository($connection));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('duplicate key');

        $service->commit([
            ['student_id' => 'S001', 'assessment_id' => 3, 'score' => 87.5, 'import_job_id' => 9],
        ]);
    }
}
