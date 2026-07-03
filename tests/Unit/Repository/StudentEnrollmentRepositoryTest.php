<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\StudentEnrollmentRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class StudentEnrollmentRepositoryTest extends TestCase
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
                self::stringContains('INSERT INTO student_enrollments'),
                ['S001', 1, 5, 6, 2569, 'active'],
            )
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new StudentEnrollmentRepository($connection);

        $id = $repository->create([
            'student_id' => 'S001',
            'school_id' => 1,
            'classroom_id' => 5,
            'grade_level' => 6,
            'academic_year' => 2569,
        ]);

        self::assertSame(7, $id);
    }

    public function testCreateAcceptsAnExplicitEnrollmentStatus(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('8');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::anything(), ['S002', 1, 5, 6, 2569, 'repeated'])
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new StudentEnrollmentRepository($connection);

        $repository->create([
            'student_id' => 'S002',
            'school_id' => 1,
            'classroom_id' => 5,
            'grade_level' => 6,
            'academic_year' => 2569,
            'enrollment_status' => 'repeated',
        ]);
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
                    self::stringContains('enrollment_status = ?'),
                    self::stringContains('WHERE id = ?'),
                ),
                ['transferred', 7],
            )
            ->willReturn($statement);

        $repository = new StudentEnrollmentRepository($connection);

        self::assertTrue($repository->update(7, ['enrollment_status' => 'transferred']));
    }

    public function testDeleteScopesById(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(1);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE id = ?'), [7])
            ->willReturn($statement);

        $repository = new StudentEnrollmentRepository($connection);

        self::assertTrue($repository->delete(7));
    }

    public function testFindByStudentOrdersByAcademicYearAscending(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([
            ['academic_year' => 2567],
            ['academic_year' => 2568],
        ]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('ORDER BY academic_year ASC'), ['S001'])
            ->willReturn($statement);

        $repository = new StudentEnrollmentRepository($connection);

        self::assertSame(
            [['academic_year' => 2567], ['academic_year' => 2568]],
            $repository->findByStudent('S001'),
        );
    }

    public function testFindByStudentAndYearReturnsTheMatchingRow(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(['id' => 7, 'academic_year' => 2569]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('WHERE student_id = ?'),
                    self::stringContains('AND academic_year = ?'),
                ),
                ['S001', 2569],
            )
            ->willReturn($statement);

        $repository = new StudentEnrollmentRepository($connection);

        self::assertSame(['id' => 7, 'academic_year' => 2569], $repository->findByStudentAndYear('S001', 2569));
    }

    public function testFindByStudentAndYearReturnsNullWhenNothingMatches(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(false);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturn($statement);

        $repository = new StudentEnrollmentRepository($connection);

        self::assertNull($repository->findByStudentAndYear('S001', 2569));
    }

    public function testFindCurrentForStudentOrdersDescendingAndLimitsToOne(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(['id' => 9, 'academic_year' => 2569]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('ORDER BY academic_year DESC'),
                    self::stringContains('LIMIT 1'),
                ),
                ['S001'],
            )
            ->willReturn($statement);

        $repository = new StudentEnrollmentRepository($connection);

        self::assertSame(['id' => 9, 'academic_year' => 2569], $repository->findCurrentForStudent('S001'));
    }
}
