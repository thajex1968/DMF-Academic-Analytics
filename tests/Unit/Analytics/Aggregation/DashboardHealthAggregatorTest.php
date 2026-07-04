<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Aggregation;

use DMF\Analytics\Aggregation\DashboardHealthAggregator;
use DMF\Repository\AssessmentRepository;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\StudentRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DashboardHealthAggregatorTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $failedJobs;

    /** @var array<string, mixed>|null */
    private ?array $latestAssessment;

    private int $studentCount;

    private int $assessmentCount;

    private ConnectionInterface $connection;

    protected function setUp(): void
    {
        $this->failedJobs = [];
        $this->latestAssessment = ['id' => 5, 'subject_code' => 'MATH', 'academic_year' => 2569];
        $this->studentCount = 120;
        $this->assessmentCount = 3;

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturnCallback(
            fn (string $sql, array $params = []): PDOStatement => $this->fakeExecute($sql, $params),
        );

        $this->connection = $connection;
    }

    private function aggregator(): DashboardHealthAggregator
    {
        return new DashboardHealthAggregator(
            new ImportJobRepository($this->connection),
            new AssessmentRepository($this->connection),
            new StudentRepository($this->connection),
        );
    }

    public function testReportsOkWithNoWarningsWhenThereAreNoFailedJobsAndAssessmentsExist(): void
    {
        $health = $this->aggregator()->build();

        self::assertSame('ok', $health->importStatus);
        self::assertSame('ok', $health->analyticsStatus);
        self::assertSame(5, $health->latestAssessmentId);
        self::assertSame('MATH', $health->latestAssessmentSubjectCode);
        self::assertSame(2569, $health->latestAssessmentAcademicYear);
        self::assertNull($health->latestCalculation);
        self::assertSame(120, $health->totalStudents);
        self::assertSame(3, $health->totalAssessments);
        self::assertSame([], $health->warnings);
    }

    public function testReportsDegradedWithAWarningWhenFailedJobsExist(): void
    {
        $this->failedJobs = [['id' => 9, 'status' => 'failed']];

        $health = $this->aggregator()->build();

        self::assertSame('degraded', $health->importStatus);
        self::assertCount(1, $health->warnings);
        self::assertSame('import:failed', $health->warnings[0]->identifier);
    }

    public function testReportsAWarningAndNullLatestAssessmentWhenNoAssessmentExistsYet(): void
    {
        $this->latestAssessment = null;
        $this->assessmentCount = 0;

        $health = $this->aggregator()->build();

        self::assertNull($health->latestAssessmentId);
        self::assertNull($health->latestAssessmentSubjectCode);
        self::assertNull($health->latestAssessmentAcademicYear);
        self::assertCount(1, $health->warnings);
        self::assertSame('assessments:none', $health->warnings[0]->identifier);
    }

    /** @param array<int, mixed> $params */
    private function fakeExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->createMock(PDOStatement::class);

        if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'FROM students')) {
            $statement->method('fetch')->willReturn(['n' => $this->studentCount]);

            return $statement;
        }

        if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'FROM assessments')) {
            $statement->method('fetch')->willReturn(['n' => $this->assessmentCount]);

            return $statement;
        }

        if (str_contains($sql, 'ORDER BY academic_year DESC')) {
            $statement->method('fetch')->willReturn($this->latestAssessment ?? false);

            return $statement;
        }

        if (str_contains($sql, 'FROM import_jobs') && str_contains($sql, 'WHERE status = ?')) {
            $statement->method('fetchAll')->willReturn($this->failedJobs);

            return $statement;
        }

        throw new RuntimeException(sprintf('Unhandled SQL in DashboardHealthAggregatorTest mock: %s', $sql));
    }
}
