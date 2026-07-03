<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Normalization;

use DMF\Analytics\Normalization\QuestionStandardResolver;
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

/**
 * All five repositories QuestionStandardResolver depends on are `final` —
 * exercised as real instances over one mocked ConnectionInterface serving
 * the Golden Dataset catalogue (NormalizationFixtures), same pattern as
 * ScoreImportServiceTest.
 */
final class QuestionStandardResolverTest extends TestCase
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $state;

    private QuestionStandardResolver $resolver;

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

        $this->resolver = new QuestionStandardResolver(
            new QuestionRepository($connection),
            new QuestionSecondaryIndicatorRepository($connection),
            new LearningIndicatorRepository($connection),
            new LearningStandardRepository($connection),
            new LearningStrandRepository($connection),
        );
    }

    public function testResolvesAPrimaryIndicatorWithItsFullStandardAndStrandChain(): void
    {
        $indicator = $this->resolver->resolvePrimaryIndicator(101);

        self::assertSame(1, $indicator->id);
        self::assertSame('ค1.1 ป.6/1', $indicator->indicatorCode);
        self::assertSame(1, $indicator->standard->id);
        self::assertSame('ค1.1', $indicator->standard->standardCode);
        self::assertSame(1, $indicator->standard->strand->id);
        self::assertSame('ค1', $indicator->standard->strand->strandCode);
    }

    public function testAQuestionWithNoSecondaryLinksResolvesAnEmptyList(): void
    {
        self::assertSame([], $this->resolver->resolveSecondaryIndicators(101));
    }

    public function testResolvesSecondaryIndicators(): void
    {
        $secondary = $this->resolver->resolveSecondaryIndicators(102);

        self::assertCount(1, $secondary);
        self::assertSame(2, $secondary[0]->id);
    }

    public function testAMissingPrimaryIndicatorRaisesAnUnresolvedMappingException(): void
    {
        $this->expectException(UnresolvedMappingException::class);
        $this->expectExceptionMessage('Learning indicator 999 not found.');

        $this->resolver->resolvePrimaryIndicator(104);
    }

    public function testAnUnknownQuestionIdRaisesAnUnresolvedMappingException(): void
    {
        $this->expectException(UnresolvedMappingException::class);
        $this->expectExceptionMessage('Question 9999 not found.');

        $this->resolver->resolvePrimaryIndicator(9999);
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

        throw new RuntimeException(sprintf('Unhandled SQL in QuestionStandardResolverTest mock: %s', $sql));
    }
}
