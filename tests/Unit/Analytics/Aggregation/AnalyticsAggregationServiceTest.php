<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Aggregation;

use DateTimeImmutable;
use DMF\Analytics\Aggregation\AnalyticsAggregationService;
use DMF\Analytics\Aggregation\AssessmentSummaryAggregator;
use DMF\Analytics\Aggregation\BenchmarkAggregator;
use DMF\Analytics\Aggregation\StandardSummaryAggregator;
use DMF\Analytics\Aggregation\StrandSummaryAggregator;
use DMF\Analytics\Aggregation\SubjectSummaryAggregator;
use DMF\Analytics\Cache\InMemoryDashboardCache;
use DMF\Analytics\Calculators\BenchmarkCalculator;
use DMF\Analytics\Calculators\DifficultyCalculator;
use DMF\Analytics\Calculators\StandardPerformanceCalculator;
use DMF\Analytics\Calculators\StrandPerformanceCalculator;
use DMF\Analytics\Calculators\SubjectPerformanceCalculator;
use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Canonical\AssessmentAnalyticsRecord;
use DMF\Analytics\Context\AnalyticsContextFactory;
use DMF\Analytics\Normalization\ItemIndicatorNormalizer;
use DMF\Analytics\Normalization\QuestionStandardResolver;
use DMF\Analytics\Normalization\StandardMappingService;
use DMF\Analytics\Pipeline\AnalyticsPipeline;
use DMF\Analytics\Result\AnalyticsResult;
use DMF\Analytics\Result\AnalyticsWarning;
use DMF\Analytics\Result\StandardResult;
use DMF\Analytics\Result\StrandResult;
use DMF\Analytics\Result\SubjectResult;
use DMF\Repository\AnalyticsReadRepository;
use DMF\Repository\AssessmentRepository;
use DMF\Repository\LearningIndicatorRepository;
use DMF\Repository\LearningStandardRepository;
use DMF\Repository\LearningStrandRepository;
use DMF\Repository\QuestionRepository;
use DMF\Repository\QuestionSecondaryIndicatorRepository;
use DMF\Tests\Fixtures\Normalization\NormalizationFixtures;
use Dmf\Core\Contract\ConnectionInterface;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AnalyticsAggregationServiceTest extends TestCase
{
    public function testAggregateMergesEachCalculatorsRecordsAndWarningsIntoOneDashboardResponse(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 2, 4, 3),
            [],
            [],
            [],
            [],
        );

        $subjectResult = AnalyticsResult::build(
            'subject-performance',
            [new SubjectResult('MATH', 0.75, null, null, null, null)],
            [new AnalyticsWarning('subject:MATH', 'unavailable')],
            [],
            new DateTimeImmutable(),
        );
        $strandResult = AnalyticsResult::build(
            'strand-performance',
            [new StrandResult(10, 'ST-A', 'MATH', 0.75, 2, 4, 3)],
            [],
            [],
            new DateTimeImmutable(),
        );
        $standardResult = AnalyticsResult::build(
            'standard-performance',
            [new StandardResult(100, 'STD-A1', 0.75, null, null, null, null, null)],
            [new AnalyticsWarning('standard:100', 'unavailable')],
            [],
            new DateTimeImmutable(),
        );
        $benchmarkResult = AnalyticsResult::build('benchmark', [], [], [], new DateTimeImmutable());

        $response = $this->service()->aggregate(
            $context,
            [$subjectResult, $strandResult, $standardResult, $benchmarkResult],
        );

        self::assertSame(3, $response->metadata->assessmentId);
        self::assertCount(1, $response->assessments);
        self::assertSame(0.75, $response->assessments[0]->percentCorrect);
        self::assertCount(1, $response->subjects);
        self::assertCount(1, $response->strands);
        self::assertCount(1, $response->standards);
        self::assertCount(0, $response->benchmarks);
        self::assertCount(2, $response->warnings);
        self::assertSame(2, $response->summary->totalStudents);
        self::assertSame(4, $response->summary->totalResponses);
        self::assertCount(3, $response->summary->cards);
        self::assertCount(1, $response->summary->datasets);
    }

    public function testForLatestAssessmentOrchestratesTheFullPipelineAgainstTheGoldenDataset(): void
    {
        $service = $this->orchestratedService();

        $response = $service->forLatestAssessment();

        self::assertNotNull($response);
        self::assertSame(3, $response->metadata->assessmentId);
        self::assertSame(2, $response->assessments[0]->studentCount);
        self::assertSame(4, $response->assessments[0]->responseCount);
        self::assertSame(3, $response->assessments[0]->correctCount);
        self::assertSame(0.75, $response->assessments[0]->percentCorrect);
        self::assertCount(1, $response->subjects);
        self::assertSame('MATH', $response->subjects[0]->subjectCode);
        self::assertCount(1, $response->strands);
        self::assertCount(1, $response->standards);
        self::assertSame([], $response->benchmarks);
    }

    public function testForLatestAssessmentReturnsNullWhenNoAssessmentExistsYet(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(false);
        $connection->method('execute')->willReturn($statement);

        $service = new AnalyticsAggregationService(
            new AssessmentRepository($connection),
            new AnalyticsReadRepository($connection),
            new ItemIndicatorNormalizer(new StandardMappingService(new QuestionStandardResolver(
                new QuestionRepository($connection),
                new QuestionSecondaryIndicatorRepository($connection),
                new LearningIndicatorRepository($connection),
                new LearningStandardRepository($connection),
                new LearningStrandRepository($connection),
            ))),
            new AnalyticsContextFactory(),
            new AnalyticsPipeline([]),
            new AssessmentSummaryAggregator(),
            new SubjectSummaryAggregator(),
            new StrandSummaryAggregator(),
            new StandardSummaryAggregator(),
            new BenchmarkAggregator(),
        );

        self::assertNull($service->forLatestAssessment());
    }

    public function testForLatestAssessmentServesFromCacheOnASecondCallWithoutRequeryingResponses(): void
    {
        $cache = new InMemoryDashboardCache();
        $queryCount = 0;

        $service = $this->orchestratedService($cache, $queryCount);

        $first = $service->forLatestAssessment();
        $countAfterFirst = $queryCount;
        $second = $service->forLatestAssessment();

        self::assertSame($first, $second, 'the second call must be served from cache, not recomputed');
        self::assertSame($countAfterFirst, $queryCount, 'no new response-table query should run on a cache hit');
    }

    private function service(): AnalyticsAggregationService
    {
        $connection = $this->createMock(ConnectionInterface::class);

        return new AnalyticsAggregationService(
            new AssessmentRepository($connection),
            new AnalyticsReadRepository($connection),
            new ItemIndicatorNormalizer(new StandardMappingService(new QuestionStandardResolver(
                new QuestionRepository($connection),
                new QuestionSecondaryIndicatorRepository($connection),
                new LearningIndicatorRepository($connection),
                new LearningStandardRepository($connection),
                new LearningStrandRepository($connection),
            ))),
            new AnalyticsContextFactory(),
            new AnalyticsPipeline([]),
            new AssessmentSummaryAggregator(),
            new SubjectSummaryAggregator(),
            new StrandSummaryAggregator(),
            new StandardSummaryAggregator(),
            new BenchmarkAggregator(),
        );
    }

    private function orchestratedService(
        ?InMemoryDashboardCache $cache = null,
        int &$queryCount = 0,
    ): AnalyticsAggregationService {
        $state = [
            'questions' => NormalizationFixtures::questions(),
            'question_secondary_indicators' => NormalizationFixtures::secondaryIndicatorLinks(),
            'learning_indicators' => NormalizationFixtures::indicators(),
            'learning_standards' => NormalizationFixtures::standards(),
            'learning_strands' => NormalizationFixtures::strands(),
        ];
        $assessmentRow = ['id' => 3, 'subject_code' => 'MATH', 'academic_year' => 2569, 'grade_level' => 6];
        $responses = NormalizationFixtures::responses();

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturnCallback(
            function (
                string $sql,
                array $params = []
            ) use (
                &$queryCount,
                $state,
                $assessmentRow,
                $responses,
            ): PDOStatement {
                $statement = $this->createMock(PDOStatement::class);

                foreach (array_keys($state) as $table) {
                    if (str_contains($sql, "FROM {$table}") && str_contains($sql, 'WHERE id = ?')) {
                        $row = $state[$table][(int) $params[0]] ?? false;
                        $statement->method('fetch')->willReturn($row);

                        return $statement;
                    }
                }

                $isSecondaryIndicatorLookup = str_contains($sql, 'FROM question_secondary_indicators')
                    && str_contains($sql, 'WHERE question_id = ?');

                if ($isSecondaryIndicatorLookup) {
                    $questionId = (int) $params[0];
                    $rows = array_values(array_filter(
                        $state['question_secondary_indicators'],
                        static fn (array $row): bool => $row['question_id'] === $questionId,
                    ));
                    $statement->method('fetchAll')->willReturn($rows);

                    return $statement;
                }

                if (str_contains($sql, 'ORDER BY academic_year DESC')) {
                    $statement->method('fetch')->willReturn($assessmentRow);

                    return $statement;
                }

                if (str_contains($sql, 'FROM student_question_responses')) {
                    $queryCount++;
                    $statement->method('fetchAll')->willReturn($responses);

                    return $statement;
                }

                throw new RuntimeException(sprintf('Unhandled SQL in AnalyticsAggregationServiceTest mock: %s', $sql));
            },
        );

        $pipeline = new AnalyticsPipeline([
            new DifficultyCalculator(),
            new BenchmarkCalculator(),
            new StandardPerformanceCalculator(),
            new SubjectPerformanceCalculator(),
            new StrandPerformanceCalculator(),
        ]);

        return new AnalyticsAggregationService(
            new AssessmentRepository($connection),
            new AnalyticsReadRepository($connection),
            new ItemIndicatorNormalizer(new StandardMappingService(new QuestionStandardResolver(
                new QuestionRepository($connection),
                new QuestionSecondaryIndicatorRepository($connection),
                new LearningIndicatorRepository($connection),
                new LearningStandardRepository($connection),
                new LearningStrandRepository($connection),
            ))),
            new AnalyticsContextFactory(),
            $pipeline,
            new AssessmentSummaryAggregator(),
            new SubjectSummaryAggregator(),
            new StrandSummaryAggregator(),
            new StandardSummaryAggregator(),
            new BenchmarkAggregator(),
            $cache,
        );
    }
}
