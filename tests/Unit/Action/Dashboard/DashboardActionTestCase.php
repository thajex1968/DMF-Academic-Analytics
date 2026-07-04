<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Action\Dashboard;

use DMF\Analytics\Aggregation\AnalyticsAggregationService;
use DMF\Analytics\Aggregation\AssessmentSummaryAggregator;
use DMF\Analytics\Aggregation\BenchmarkAggregator;
use DMF\Analytics\Aggregation\StandardSummaryAggregator;
use DMF\Analytics\Aggregation\StrandSummaryAggregator;
use DMF\Analytics\Aggregation\SubjectSummaryAggregator;
use DMF\Analytics\Calculators\BenchmarkCalculator;
use DMF\Analytics\Calculators\DifficultyCalculator;
use DMF\Analytics\Calculators\StandardPerformanceCalculator;
use DMF\Analytics\Calculators\StrandPerformanceCalculator;
use DMF\Analytics\Calculators\SubjectPerformanceCalculator;
use DMF\Analytics\Context\AnalyticsContextFactory;
use DMF\Analytics\Normalization\ItemIndicatorNormalizer;
use DMF\Analytics\Normalization\QuestionStandardResolver;
use DMF\Analytics\Normalization\StandardMappingService;
use DMF\Analytics\Pipeline\AnalyticsPipeline;
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

/**
 * Shared setup for every Dashboard Action test — `AnalyticsAggregationService`
 * is `final`, so it cannot be mocked; every Action test instead drives a real
 * instance, wired against the same Normalization Golden Dataset
 * (`NormalizationFixtures`, T2.5) already proven correct by
 * `AnalyticsAggregationServiceTest`, over a mocked `ConnectionInterface`.
 */
abstract class DashboardActionTestCase extends TestCase
{
    protected function makeServiceWithAssessment(): AnalyticsAggregationService
    {
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
            function (string $sql, array $params = []) use ($state, $assessmentRow, $responses): PDOStatement {
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
                    $statement->method('fetchAll')->willReturn($responses);

                    return $statement;
                }

                throw new RuntimeException(sprintf('Unhandled SQL in %s mock: %s', static::class, $sql));
            },
        );

        return $this->buildService($connection);
    }

    protected function makeServiceWithNoAssessment(): AnalyticsAggregationService
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(false);
        $connection->method('execute')->willReturn($statement);

        return $this->buildService($connection);
    }

    private function buildService(ConnectionInterface $connection): AnalyticsAggregationService
    {
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
            new AnalyticsPipeline([
                new DifficultyCalculator(),
                new BenchmarkCalculator(),
                new StandardPerformanceCalculator(),
                new SubjectPerformanceCalculator(),
                new StrandPerformanceCalculator(),
            ]),
            new AssessmentSummaryAggregator(),
            new SubjectSummaryAggregator(),
            new StrandSummaryAggregator(),
            new StandardSummaryAggregator(),
            new BenchmarkAggregator(),
        );
    }
}
