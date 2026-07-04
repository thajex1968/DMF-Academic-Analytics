<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Calculators;

use DateTimeImmutable;
use DMF\Analytics\Calculators\DifficultyCalculator;
use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Canonical\AssessmentAnalyticsRecord;
use DMF\Analytics\Canonical\QuestionAnalyticsRecord;
use DMF\Analytics\Contracts\CalculatorExecutionContext;
use DMF\Analytics\Contracts\CalculatorPriority;
use DMF\Analytics\Result\DifficultyResult;
use PHPUnit\Framework\TestCase;

final class DifficultyCalculatorTest extends TestCase
{
    private function makeExecutionContext(AnalyticsContext $context): CalculatorExecutionContext
    {
        return new CalculatorExecutionContext($context, new DateTimeImmutable('2026-07-04T09:00:00+07:00'));
    }

    public function testNamePriorityAndCapabilities(): void
    {
        $calculator = new DifficultyCalculator();

        self::assertSame('difficulty', $calculator->name());
        self::assertSame(CalculatorPriority::HIGH, $calculator->priority());

        $capabilities = $calculator->capabilities();
        self::assertTrue($capabilities->supportsLevel1);
        self::assertTrue($capabilities->supportsLevel2);
    }

    public function testComputesADifficultyIndexPerQuestionRegardlessOfWhichLevelProducedTheCounts(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 3, 4, 3),
            [],
            [],
            [],
            [
                new QuestionAnalyticsRecord(101, 100, 2, 2, 1),
                new QuestionAnalyticsRecord(102, 100, 1, 2, 2),
            ],
        );

        $result = (new DifficultyCalculator())->calculate($this->makeExecutionContext($context));

        self::assertSame('difficulty', $result->calculatorName());
        self::assertSame([], $result->warnings());
        self::assertCount(2, $result->records());

        /** @var DifficultyResult[] $records */
        $records = $result->records();
        self::assertSame(101, $records[0]->questionId);
        self::assertSame(100, $records[0]->standardId);
        self::assertSame(0.5, $records[0]->difficultyIndex);

        self::assertSame(102, $records[1]->questionId);
        self::assertSame(1.0, $records[1]->difficultyIndex);
    }

    public function testAQuestionWithNoResponsesProducesAWarningInsteadOfADivisionByZero(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 0, 0, 0),
            [],
            [],
            [],
            [new QuestionAnalyticsRecord(101, 100, 0, 0, 0)],
        );

        $result = (new DifficultyCalculator())->calculate($this->makeExecutionContext($context));

        self::assertSame([], $result->records());
        self::assertCount(1, $result->warnings());
        self::assertSame('question:101', $result->warnings()[0]->identifier);
    }

    public function testAnEmptyContextProducesAnEmptyResultWithNoWarnings(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 0, 0, 0),
            [],
            [],
            [],
            [],
        );

        $result = (new DifficultyCalculator())->calculate($this->makeExecutionContext($context));

        self::assertSame([], $result->records());
        self::assertSame([], $result->warnings());
        self::assertSame(0, $result->summary()->recordCount);
    }
}
