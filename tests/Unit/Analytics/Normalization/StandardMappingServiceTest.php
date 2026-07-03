<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Normalization;

use DMF\Analytics\Normalization\QuestionStandardResolver;
use DMF\Analytics\Normalization\StandardMappingService;
use DMF\Analytics\Normalization\UnresolvedMappingException;
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

final class StandardMappingServiceTest extends TestCase
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $state;

    private StandardMappingService $service;

    protected function setUp(): void
    {
        $this->state = [
            'questions' => NormalizationFixtures::questions(),
            'question_secondary_indicators' => NormalizationFixtures::secondaryIndicatorLinks(),
            'learning_indicators' => NormalizationFixtures::indicators(),
            'learning_standards' => NormalizationFixtures::standards(),
            'learning_strands' => NormalizationFixtures::strands(),
        ];

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

        $this->service = new StandardMappingService($resolver);
    }

    public function testAQuestionWithOnlyAPrimaryIndicatorMapsWithNoSecondaryIndicators(): void
    {
        $mapping = $this->service->map(101);

        self::assertSame(101, $mapping->questionId);
        self::assertSame(1, $mapping->primaryIndicator->id);
        self::assertSame([], $mapping->secondaryIndicators);
    }

    public function testAQuestionWithPrimaryAndSecondaryIndicatorsMapsBoth(): void
    {
        $mapping = $this->service->map(102);

        self::assertSame(1, $mapping->primaryIndicator->id);
        self::assertCount(1, $mapping->secondaryIndicators);
        self::assertSame(2, $mapping->secondaryIndicators[0]->id);
    }

    public function testAQuestionMappedToMultipleStandardsKeepsEachIndicatorsOwnStandardAndStrand(): void
    {
        $mapping = $this->service->map(103);

        self::assertSame(1, $mapping->primaryIndicator->standard->id);
        self::assertSame(1, $mapping->primaryIndicator->standard->strand->id);

        self::assertCount(1, $mapping->secondaryIndicators);
        self::assertSame(2, $mapping->secondaryIndicators[0]->standard->id);
        self::assertSame(2, $mapping->secondaryIndicators[0]->standard->strand->id);
    }

    public function testASecondaryIndicatorEqualToThePrimaryIsNeverDoubleCounted(): void
    {
        $mapping = $this->service->map(105);

        self::assertSame(1, $mapping->primaryIndicator->id);
        self::assertSame(
            [],
            $mapping->secondaryIndicators,
            'the secondary link duplicates the primary indicator — must be dropped',
        );
    }

    public function testAMissingPrimaryIndicatorMappingPropagatesAsAnUnresolvedMappingException(): void
    {
        $this->expectException(UnresolvedMappingException::class);

        $this->service->map(104);
    }

    /** @param array<int, mixed> $params */
    private function fakeExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->createMock(PDOStatement::class);

        foreach (array_keys($this->state) as $table) {
            if (str_contains($sql, "FROM {$table}") && str_contains($sql, 'WHERE id = ?')) {
                $row = $this->state[$table][(int) $params[0]] ?? false;
                $statement->method('fetch')->willReturn($row);

                return $statement;
            }
        }

        if (str_contains($sql, 'FROM question_secondary_indicators') && str_contains($sql, 'WHERE question_id = ?')) {
            $rows = array_values(array_filter(
                $this->state['question_secondary_indicators'],
                static fn (array $row): bool => $row['question_id'] === (int) $params[0],
            ));
            $statement->method('fetchAll')->willReturn($rows);

            return $statement;
        }

        throw new RuntimeException(sprintf('Unhandled SQL in StandardMappingServiceTest mock: %s', $sql));
    }
}
