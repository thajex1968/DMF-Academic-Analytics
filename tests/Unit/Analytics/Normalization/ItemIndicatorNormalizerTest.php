<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Normalization;

use DMF\Analytics\Normalization\ItemIndicatorNormalizer;
use DMF\Analytics\Normalization\QuestionStandardResolver;
use DMF\Analytics\Normalization\StandardMappingService;
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
 * End-to-end test of ItemIndicatorNormalizer over the full Normalization
 * Golden Dataset (NormalizationFixtures), driving the real
 * StandardMappingService/QuestionStandardResolver/repository stack against
 * one mocked ConnectionInterface — same pattern as
 * QuestionStandardResolverTest/StandardMappingServiceTest.
 */
final class ItemIndicatorNormalizerTest extends TestCase
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $state;

    private ItemIndicatorNormalizer $normalizer;

    /**
     * Counts how many times each table/id pair was actually queried, to verify per-question caching.
     *
     * @var array<string, int>
     */
    private array $queryCounts = [];

    protected function setUp(): void
    {
        $this->state = [
            'questions' => NormalizationFixtures::questions(),
            'question_secondary_indicators' => NormalizationFixtures::secondaryIndicatorLinks(),
            'learning_indicators' => NormalizationFixtures::indicators(),
            'learning_standards' => NormalizationFixtures::standards(),
            'learning_strands' => NormalizationFixtures::strands(),
        ];
        $this->queryCounts = [];

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturnCallback(
            fn (string $sql, array $params = []): PDOStatement => $this->fakeExecute($sql, $params),
        );

        $resolver = new QuestionStandardResolver(
            new QuestionRepository($connection),
            new QuestionSecondaryIndicatorRepository($connection),
            new LearningIndicatorRepository($connection),
            new LearningStandardRepository($connection),
            new LearningStrandRepository($connection),
        );

        $this->normalizer = new ItemIndicatorNormalizer(new StandardMappingService($resolver));
    }

    public function testAnEmptyResponseSetNormalizesToAnEmptyResultWithZeroCounts(): void
    {
        $result = $this->normalizer->normalize([]);

        self::assertSame([], $result->records);
        self::assertSame([], $result->unresolvedMappings);
        self::assertSame(0, $result->totalRows);
        self::assertSame(0, $result->normalizedCount);
        self::assertSame(0, $result->unresolvedCount);
    }

    public function testTheFullGoldenDatasetNormalizesWithTheExpectedResolvedAndUnresolvedSplit(): void
    {
        $result = $this->normalizer->normalize(NormalizationFixtures::responses());

        self::assertSame(6, $result->totalRows);
        // Rows 1, 2, 3, 5 resolve; rows 4 (unresolvable indicator) and 6 (invalid question_id) do not.
        self::assertCount(4, $result->records);
        self::assertCount(2, $result->unresolvedMappings);
        self::assertSame(4, $result->normalizedCount);
        self::assertSame(2, $result->unresolvedCount);
    }

    public function testAQuestionWithOnlyAPrimaryIndicatorNormalizesWithNoSecondaryIndicators(): void
    {
        $result = $this->normalizer->normalize([
            ['student_id' => 'S001', 'question_id' => 101, 'selected_choice' => '1', 'is_correct' => true],
        ]);

        self::assertCount(1, $result->records);
        $record = $result->records[0];
        self::assertSame('S001', $record->studentId);
        self::assertSame('1', $record->selectedChoice);
        self::assertTrue($record->isCorrect);
        self::assertSame(101, $record->mapping->questionId);
        self::assertSame(1, $record->mapping->primaryIndicator->id);
        self::assertSame([], $record->mapping->secondaryIndicators);
    }

    public function testAQuestionWithPrimaryAndSecondaryIndicatorsNormalizesBoth(): void
    {
        $result = $this->normalizer->normalize([
            ['student_id' => 'S001', 'question_id' => 102, 'selected_choice' => '3', 'is_correct' => false],
        ]);

        $record = $result->records[0];
        self::assertSame(1, $record->mapping->primaryIndicator->id);
        self::assertCount(1, $record->mapping->secondaryIndicators);
        self::assertSame(2, $record->mapping->secondaryIndicators[0]->id);
        self::assertFalse($record->isCorrect);
    }

    public function testAQuestionMappedToMultipleStandardsKeepsEachIndicatorsOwnStandardAndStrand(): void
    {
        $result = $this->normalizer->normalize([
            ['student_id' => 'S002', 'question_id' => 103, 'selected_choice' => '3', 'is_correct' => true],
        ]);

        $record = $result->records[0];
        self::assertSame(1, $record->mapping->primaryIndicator->standard->id);
        self::assertSame(1, $record->mapping->primaryIndicator->standard->strand->id);
        self::assertSame(2, $record->mapping->secondaryIndicators[0]->standard->id);
        self::assertSame(2, $record->mapping->secondaryIndicators[0]->standard->strand->id);
    }

    public function testAnUnresolvableIndicatorProducesATracedUnresolvedMappingRatherThanAnException(): void
    {
        $result = $this->normalizer->normalize([
            ['student_id' => 'S003', 'question_id' => 104, 'selected_choice' => '4', 'is_correct' => true],
        ]);

        self::assertSame([], $result->records);
        self::assertCount(1, $result->unresolvedMappings);

        $unresolved = $result->unresolvedMappings[0];
        self::assertSame(1, $unresolved->rowNumber);
        self::assertSame(104, $unresolved->questionId);
        self::assertStringContainsString('999', $unresolved->reason);
    }

    public function testASecondaryIndicatorEqualToThePrimaryIsNeverDoubleCountedDuringNormalization(): void
    {
        $result = $this->normalizer->normalize([
            ['student_id' => 'S001', 'question_id' => 105, 'selected_choice' => '1', 'is_correct' => true],
        ]);

        $record = $result->records[0];
        self::assertSame(1, $record->mapping->primaryIndicator->id);
        self::assertSame([], $record->mapping->secondaryIndicators);
    }

    public function testAMissingQuestionIdProducesATracedUnresolvedMappingRatherThanAFatalError(): void
    {
        $result = $this->normalizer->normalize([
            ['student_id' => 'S002', 'question_id' => null, 'selected_choice' => '1', 'is_correct' => false],
        ]);

        self::assertSame([], $result->records);
        self::assertCount(1, $result->unresolvedMappings);

        $unresolved = $result->unresolvedMappings[0];
        self::assertSame(1, $unresolved->rowNumber);
        self::assertSame(0, $unresolved->questionId);
        self::assertSame('Missing or invalid question_id.', $unresolved->reason);
    }

    public function testANonNumericQuestionIdIsTreatedAsMissingRatherThanCastToZero(): void
    {
        $result = $this->normalizer->normalize([
            ['student_id' => 'S002', 'question_id' => 'not-a-number', 'selected_choice' => '1', 'is_correct' => false],
        ]);

        self::assertSame([], $result->records);
        self::assertCount(1, $result->unresolvedMappings);
        self::assertSame('Missing or invalid question_id.', $result->unresolvedMappings[0]->reason);
    }

    public function testAQuestionAnsweredByMultipleStudentsResolvesItsMappingOnlyOnce(): void
    {
        $this->normalizer->normalize([
            ['student_id' => 'S001', 'question_id' => 101, 'selected_choice' => '1', 'is_correct' => true],
            ['student_id' => 'S002', 'question_id' => 101, 'selected_choice' => '2', 'is_correct' => false],
            ['student_id' => 'S003', 'question_id' => 101, 'selected_choice' => '1', 'is_correct' => true],
        ]);

        self::assertSame(
            1,
            $this->queryCounts['questions:101'] ?? 0,
            'question 101 should only be looked up once across three responses',
        );
    }

    public function testAQuestionThatFailedToResolveOnceIsNotRetriedForSubsequentRows(): void
    {
        $result = $this->normalizer->normalize([
            ['student_id' => 'S001', 'question_id' => 104, 'selected_choice' => '4', 'is_correct' => true],
            ['student_id' => 'S002', 'question_id' => 104, 'selected_choice' => '4', 'is_correct' => true],
        ]);

        self::assertSame([], $result->records);
        self::assertCount(2, $result->unresolvedMappings);
        self::assertSame(2, $result->unresolvedMappings[1]->rowNumber);
        self::assertSame(104, $result->unresolvedMappings[1]->questionId);
        // Only one real lookup of the broken indicator id (999) even though two rows share question 104.
        self::assertSame(1, $this->queryCounts['learning_indicators:999'] ?? 0);
    }

    /** @param array<int, mixed> $params */
    private function fakeExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->createMock(PDOStatement::class);

        foreach (array_keys($this->state) as $table) {
            if (str_contains($sql, "FROM {$table}") && str_contains($sql, 'WHERE id = ?')) {
                $id = (int) $params[0];
                $this->queryCounts["{$table}:{$id}"] = ($this->queryCounts["{$table}:{$id}"] ?? 0) + 1;

                $row = $this->state[$table][$id] ?? false;
                $statement->method('fetch')->willReturn($row);

                return $statement;
            }
        }

        if (str_contains($sql, 'FROM question_secondary_indicators') && str_contains($sql, 'WHERE question_id = ?')) {
            $questionId = (int) $params[0];
            $this->queryCounts["question_secondary_indicators:{$questionId}"]
                = ($this->queryCounts["question_secondary_indicators:{$questionId}"] ?? 0) + 1;

            $rows = array_values(array_filter(
                $this->state['question_secondary_indicators'],
                static fn (array $row): bool => $row['question_id'] === $questionId,
            ));
            $statement->method('fetchAll')->willReturn($rows);

            return $statement;
        }

        throw new RuntimeException(sprintf('Unhandled SQL in ItemIndicatorNormalizerTest mock: %s', $sql));
    }
}
