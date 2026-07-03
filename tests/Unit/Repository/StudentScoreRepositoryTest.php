<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\StudentScoreRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class StudentScoreRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('55');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('INSERT INTO student_scores'), ['S001', 3, 87.5, 9])
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new StudentScoreRepository($connection);

        $id = $repository->create([
            'student_id' => 'S001',
            'assessment_id' => 3,
            'score' => 87.5,
            'import_job_id' => 9,
        ]);

        self::assertSame(55, $id);
    }

    public function testCreateDoesNotSetAnUpdatedAtColumn(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('55');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::logicalNot(self::stringContains('updated_at')), self::anything())
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        (new StudentScoreRepository($connection))->create([
            'student_id' => 'S001',
            'assessment_id' => 3,
            'score' => 87.5,
            'import_job_id' => 9,
        ]);
    }

    public function testExistsForStudentAndAssessmentReturnsTrueWhenARowMatches(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(['1' => 1]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('WHERE student_id = ?'),
                    self::stringContains('AND assessment_id = ?'),
                ),
                ['S001', 3],
            )
            ->willReturn($statement);

        $repository = new StudentScoreRepository($connection);

        self::assertTrue($repository->existsForStudentAndAssessment('S001', 3));
    }

    public function testExistsForStudentAndAssessmentReturnsFalseWhenNothingMatches(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(false);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturn($statement);

        $repository = new StudentScoreRepository($connection);

        self::assertFalse($repository->existsForStudentAndAssessment('S404', 3));
    }
}
