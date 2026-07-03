<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\AssessmentRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class AssessmentRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('3');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::stringContains('INSERT INTO assessments'),
                [1, 'MATH', 6, 2569, 'O-NET ป.6 คณิตศาสตร์ ปีการศึกษา 2569'],
            )
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new AssessmentRepository($connection);

        $id = $repository->create([
            'assessment_type_id' => 1,
            'subject_code' => 'MATH',
            'grade_level' => 6,
            'academic_year' => 2569,
            'name_th' => 'O-NET ป.6 คณิตศาสตร์ ปีการศึกษา 2569',
        ]);

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
                    self::stringContains('name_th = ?'),
                    self::stringContains('updated_at = NOW()'),
                    self::stringContains('WHERE id = ?'),
                ),
                ['New Name', 3],
            )
            ->willReturn($statement);

        $repository = new AssessmentRepository($connection);

        self::assertTrue($repository->update(3, ['name_th' => 'New Name']));
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

        $repository = new AssessmentRepository($connection);

        self::assertTrue($repository->delete(3));
    }
}
