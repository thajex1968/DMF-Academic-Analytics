<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Calculators;

use DateTimeImmutable;
use DMF\Analytics\Calculators\StandardPerformanceCalculator;
use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Canonical\AssessmentAnalyticsRecord;
use DMF\Analytics\Canonical\StandardAnalyticsRecord;
use DMF\Analytics\Contracts\CalculatorExecutionContext;
use DMF\Analytics\Contracts\CalculatorPriority;
use DMF\Analytics\Result\StandardResult;
use PHPUnit\Framework\TestCase;

final class StandardPerformanceCalculatorTest extends TestCase
{
    private function makeExecutionContext(AnalyticsContext $context): CalculatorExecutionContext
    {
        return new CalculatorExecutionContext($context, new DateTimeImmutable('2026-07-04T09:00:00+07:00'));
    }

    public function testNamePriorityAndCapabilities(): void
    {
        $calculator = new StandardPerformanceCalculator();

        self::assertSame('standard-performance', $calculator->name());
        self::assertSame(CalculatorPriority::NORMAL, $calculator->priority());

        $capabilities = $calculator->capabilities();
        self::assertTrue($capabilities->supportsLevel1);
        self::assertTrue($capabilities->supportsLevel2);
    }

    public function testComputesPercentCorrectButWarnsThatFiveStatisticsAreUnavailable(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 2, 3, 2),
            [],
            [],
            [new StandardAnalyticsRecord(100, 'STD-A1', 10, 2, 3, 2)],
            [],
        );

        $result = (new StandardPerformanceCalculator())->calculate($this->makeExecutionContext($context));

        self::assertCount(1, $result->records());
        self::assertCount(1, $result->warnings());
        self::assertSame('standard:100', $result->warnings()[0]->identifier);
        self::assertStringContainsString('per-student score distribution', $result->warnings()[0]->message);

        /** @var StandardResult $record */
        $record = $result->records()[0];
        self::assertSame(100, $record->standardId);
        self::assertEqualsWithDelta(2 / 3, $record->percentCorrect, 0.0001);
        self::assertNull($record->mean);
        self::assertNull($record->median);
        self::assertNull($record->min);
        self::assertNull($record->max);
        self::assertNull($record->standardDeviation);
    }

    public function testAStandardWithNoResponsesProducesAllNullFieldsAndOneCombinedWarning(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 0, 0, 0),
            [],
            [],
            [new StandardAnalyticsRecord(100, 'STD-A1', 10, 0, 0, 0)],
            [],
        );

        $result = (new StandardPerformanceCalculator())->calculate($this->makeExecutionContext($context));

        self::assertCount(1, $result->records());
        self::assertCount(1, $result->warnings());

        /** @var StandardResult $record */
        $record = $result->records()[0];
        self::assertNull($record->percentCorrect);
        self::assertNull($record->mean);
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

        $result = (new StandardPerformanceCalculator())->calculate($this->makeExecutionContext($context));

        self::assertSame([], $result->records());
        self::assertSame([], $result->warnings());
    }
}
