<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Audit;

use DMF\Import\Audit\DuplicateCheckResult;
use DMF\Import\Audit\DuplicateDetectionService;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\StudentScoreRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * DuplicateDetectionService exercised in isolation over a mocked
 * ConnectionInterface (real StudentScoreRepository/ImportJobRepository,
 * same pattern as every other repository-backed unit test in this project).
 */
final class DuplicateDetectionServiceTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $state;

    private DuplicateDetectionService $service;

    protected function setUp(): void
    {
        $this->state = ['scores' => [], 'jobs' => []];

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturnCallback(
            fn (string $sql, array $params = []): PDOStatement => $this->fakeExecute($sql, $params),
        );

        $this->service = new DuplicateDetectionService(
            new StudentScoreRepository($connection),
            new ImportJobRepository($connection),
        );
    }

    public function testNoDuplicatesReturnsAStructuredCleanResult(): void
    {
        $rows = [
            ['student_id' => 'S001', 'score' => '80'],
            ['student_id' => 'S002', 'score' => '90'],
        ];

        $result = $this->service->detect(1, 1, 3, '/tmp/file.xlsx', $rows);

        self::assertInstanceOf(
            DuplicateCheckResult::class,
            $result,
            'a structured result is always returned, never null/void',
        );
        self::assertFalse($result->hasDuplicates());
        self::assertSame([], $result->withinFileDuplicates);
        self::assertSame([], $result->alreadyImportedDuplicates);
        self::assertNull($result->duplicateImportJob);
        self::assertSame([], $result->activeDuplicateJobIds);
    }

    public function testDetectsAStudentIdRepeatedWithinTheFile(): void
    {
        $rows = [
            ['student_id' => 'S001', 'score' => '80'],
            ['student_id' => 'S002', 'score' => '70'],
            ['student_id' => 'S001', 'score' => '85'],
        ];

        $result = $this->service->detect(1, 1, 3, '/tmp/file.xlsx', $rows);

        self::assertTrue($result->hasDuplicates());
        self::assertSame(['S001' => [2, 4]], $result->withinFileDuplicates);
    }

    public function testDetectsAStudentAlreadyImportedForTheSameAssessment(): void
    {
        $this->state['scores'][] = ['S001', 3, 90.0, 999];

        $result = $this->service->detect(1, 1, 3, '/tmp/file.xlsx', [['student_id' => 'S001', 'score' => '80']]);

        self::assertTrue($result->hasDuplicates());
        self::assertSame([2 => 'S001'], $result->alreadyImportedDuplicates);
    }

    public function testAStudentAlreadyImportedForADifferentAssessmentIsNotADuplicate(): void
    {
        $this->state['scores'][] = ['S001', 4, 90.0, 999];

        $result = $this->service->detect(1, 1, 3, '/tmp/file.xlsx', [['student_id' => 'S001', 'score' => '80']]);

        self::assertFalse($result->hasDuplicates());
    }

    public function testDuplicateByAssessmentIsHowThisSchemaExpressesDuplicateBySubject(): void
    {
        // FR-007's business rule is "uniqueness on (academic year, subject, student ID)" — this
        // schema resolves one academic_year+subject pair to exactly one assessment_id
        // (docs/03-Database-Design.md §4), so checking (student_id, assessment_id) already *is*
        // checking (student_id, academic_year, subject); there is no separate subject-keyed check.
        $this->state['scores'][] = ['S001', 3, 90.0, 999];

        $row = [['student_id' => 'S001', 'score' => '80']];
        $sameAssessment = $this->service->detect(1, 1, 3, '/tmp/a.xlsx', $row);
        $differentAssessment = $this->service->detect(1, 1, 5, '/tmp/b.xlsx', $row);

        self::assertTrue($sameAssessment->hasDuplicates());
        self::assertFalse($differentAssessment->hasDuplicates());
    }

    public function testDetectsAnotherImportJobAlreadyRegisteredForTheSamePath(): void
    {
        $this->state['jobs'][7] = [
            'id' => 7, 'school_id' => 1, 'assessment_id' => 3, 'file_path' => '/tmp/file.xlsx', 'status' => 'committed',
        ];

        $result = $this->service->detect(9, 1, 3, '/tmp/file.xlsx', []);

        self::assertNotNull($result->duplicateImportJob);
        self::assertSame(7, $result->duplicateImportJob['id']);
    }

    public function testTheCurrentJobNeverFlagsItselfAsADuplicateImportJob(): void
    {
        $this->state['jobs'][9] = [
            'id' => 9, 'school_id' => 1, 'assessment_id' => 3,
            'file_path' => '/tmp/file.xlsx', 'status' => 'processing',
        ];

        $result = $this->service->detect(9, 1, 3, '/tmp/file.xlsx', []);

        self::assertNull($result->duplicateImportJob);
    }

    public function testDetectsAnActiveConcurrentJobForTheSameSchoolAndAssessmentExcludingItself(): void
    {
        $this->state['jobs'][9] = [
            'id' => 9, 'school_id' => 1, 'assessment_id' => 3, 'file_path' => '/tmp/a.xlsx', 'status' => 'processing',
        ];
        $this->state['jobs'][10] = [
            'id' => 10, 'school_id' => 1, 'assessment_id' => 3, 'file_path' => '/tmp/b.xlsx', 'status' => 'queued',
        ];

        $result = $this->service->detect(9, 1, 3, '/tmp/a.xlsx', []);

        self::assertSame([10], $result->activeDuplicateJobIds);
    }

    public function testAJobThatIsAlreadyCommittedOrFailedIsNotConsideredAnActiveDuplicate(): void
    {
        $this->state['jobs'][10] = [
            'id' => 10, 'school_id' => 1, 'assessment_id' => 3, 'file_path' => '/tmp/b.xlsx', 'status' => 'committed',
        ];
        $this->state['jobs'][11] = [
            'id' => 11, 'school_id' => 1, 'assessment_id' => 3, 'file_path' => '/tmp/c.xlsx', 'status' => 'failed',
        ];

        $result = $this->service->detect(9, 1, 3, '/tmp/a.xlsx', []);

        self::assertSame([], $result->activeDuplicateJobIds);
    }

    public function testContentHashIsOrderIndependentForTheSameStudentScorePairs(): void
    {
        $a = [['student_id' => 'S001', 'score' => '80'], ['student_id' => 'S002', 'score' => '90']];
        $b = [['student_id' => 'S002', 'score' => '90'], ['student_id' => 'S001', 'score' => '80']];

        self::assertSame($this->service->contentHash($a), $this->service->contentHash($b));
    }

    public function testContentHashDiffersWhenScoresDiffer(): void
    {
        $a = [['student_id' => 'S001', 'score' => '80']];
        $b = [['student_id' => 'S001', 'score' => '81']];

        self::assertNotSame($this->service->contentHash($a), $this->service->contentHash($b));
    }

    public function testSummaryNeverContainsRawExceptionOrSqlDetail(): void
    {
        $this->state['scores'][] = ['S001', 3, 90.0, 999];
        $rows = [['student_id' => 'S001', 'score' => '80'], ['student_id' => 'S001', 'score' => '85']];

        $summary = $this->service->detect(1, 1, 3, '/tmp/file.xlsx', $rows)->summary();

        self::assertStringNotContainsString('SQLSTATE', $summary);
        self::assertStringNotContainsString('PDOException', $summary);
        self::assertStringNotContainsString('Stack trace', $summary);
        self::assertStringContainsString('Duplicate student_id', $summary);
    }

    /** @param array<int, mixed> $params */
    private function fakeExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->createMock(PDOStatement::class);

        if (str_contains($sql, 'FROM student_scores') && str_contains($sql, 'student_id = ?')) {
            $exists = false;

            foreach ($this->state['scores'] as $row) {
                if ($row[0] === (string) $params[0] && (int) $row[1] === (int) $params[1]) {
                    $exists = true;

                    break;
                }
            }

            $statement->method('fetch')->willReturn($exists ? [1] : false);

            return $statement;
        }

        if (str_contains($sql, 'FROM import_jobs') && str_contains($sql, 'file_path = ?')) {
            $row = false;

            foreach ($this->state['jobs'] as $job) {
                if (
                    (int) $job['school_id'] === (int) $params[0]
                    && (int) $job['assessment_id'] === (int) $params[1]
                    && $job['file_path'] === (string) $params[2]
                ) {
                    $row = $job;

                    break;
                }
            }

            $statement->method('fetch')->willReturn($row);

            return $statement;
        }

        if (str_contains($sql, 'FROM import_jobs') && str_contains($sql, 'status IN')) {
            $excludeId = $params[2] ?? null;
            $matches = array_values(array_filter(
                $this->state['jobs'],
                static fn (array $job): bool => (int) $job['school_id'] === (int) $params[0]
                    && (int) $job['assessment_id'] === (int) $params[1]
                    && in_array($job['status'], ['queued', 'processing'], true)
                    && ($excludeId === null || (int) $job['id'] !== (int) $excludeId),
            ));
            $statement->method('fetchAll')->willReturn($matches);

            return $statement;
        }

        throw new RuntimeException(sprintf('Unhandled SQL in DuplicateDetectionServiceTest mock: %s', $sql));
    }
}
