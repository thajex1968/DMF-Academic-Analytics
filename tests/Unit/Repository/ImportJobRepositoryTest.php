<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\ImportJobRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class ImportJobRepositoryTest extends TestCase
{
    public function testCreateInsertsAndReturnsTheLastInsertId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('9');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::stringContains('INSERT INTO import_jobs'),
                [1, 1, 'storage/imports/abc_scores.xlsx', 'xlsx', 'queued', 2],
            )
            ->willReturn($statement);
        $connection->method('pdo')->willReturn($pdo);

        $repository = new ImportJobRepository($connection);

        $id = $repository->create([
            'school_id' => 1,
            'assessment_id' => 1,
            'file_path' => 'storage/imports/abc_scores.xlsx',
            'file_type' => 'xlsx',
            'uploaded_by' => 2,
        ]);

        self::assertSame(9, $id);
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
                    self::stringContains('status = ?'),
                    self::stringContains('updated_at = NOW()'),
                    self::stringContains('WHERE id = ?'),
                ),
                ['committed', 9],
            )
            ->willReturn($statement);

        $repository = new ImportJobRepository($connection);

        self::assertTrue($repository->update(9, ['status' => 'committed']));
    }

    public function testDeleteScopesById(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(1);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::stringContains('WHERE id = ?'), [9])
            ->willReturn($statement);

        $repository = new ImportJobRepository($connection);

        self::assertTrue($repository->delete(9));
    }

    public function testFindBySchoolAssessmentAndPathReturnsTheMatchingRow(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(['id' => 9]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(self::anything(), [1, 1, 'storage/imports/x.csv'])
            ->willReturn($statement);

        $repository = new ImportJobRepository($connection);

        self::assertSame(['id' => 9], $repository->findBySchoolAssessmentAndPath(1, 1, 'storage/imports/x.csv'));
    }

    public function testFindBySchoolAssessmentAndPathReturnsNullWhenNothingMatches(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(false);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturn($statement);

        $repository = new ImportJobRepository($connection);

        self::assertNull($repository->findBySchoolAssessmentAndPath(1, 1, 'storage/imports/x.csv'));
    }

    public function testFindQueuedReturnsQueuedJobsOrderedOldestFirst(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['id' => 9, 'status' => 'queued']]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('WHERE status = ?'),
                    self::stringContains('ORDER BY created_at ASC, id ASC'),
                ),
                ['queued'],
            )
            ->willReturn($statement);

        $repository = new ImportJobRepository($connection);

        self::assertSame([['id' => 9, 'status' => 'queued']], $repository->findQueued());
    }

    public function testFindActiveJobsForSchoolAndAssessmentExcludesTheGivenJobId(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([['id' => 5, 'status' => 'processing']]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains("status IN ('queued', 'processing')"),
                    self::stringContains('id != ?'),
                ),
                [1, 3, 9],
            )
            ->willReturn($statement);

        $repository = new ImportJobRepository($connection);

        self::assertSame(
            [['id' => 5, 'status' => 'processing']],
            $repository->findActiveJobsForSchoolAndAssessment(1, 3, 9),
        );
    }

    public function testFindActiveJobsForSchoolAndAssessmentWithoutAnExcludeIdOmitsTheIdFilter(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn([]);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains("status IN ('queued', 'processing')"),
                    self::logicalNot(self::stringContains('id != ?')),
                ),
                [1, 3],
            )
            ->willReturn($statement);

        $repository = new ImportJobRepository($connection);

        self::assertSame([], $repository->findActiveJobsForSchoolAndAssessment(1, 3));
    }
}
