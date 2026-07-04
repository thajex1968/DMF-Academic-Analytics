<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Calculators;

use DateTimeImmutable;
use DMF\Analytics\Calculators\StrandPerformanceCalculator;
use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Canonical\AssessmentAnalyticsRecord;
use DMF\Analytics\Canonical\StrandAnalyticsRecord;
use DMF\Analytics\Contracts\CalculatorExecutionContext;
use DMF\Analytics\Contracts\CalculatorPriority;
use DMF\Analytics\Result\StrandResult;
use PHPUnit\Framework\TestCase;

final class StrandPerformanceCalculatorTest extends TestCase
{
    private function makeExecutionContext(AnalyticsContext $context): CalculatorExecutionContext
    {
        return new CalculatorExecutionContext($context, new DateTimeImmutable('2026-07-04T09:00:00+07:00'));
    }

    public function testNamePriorityAndCapabilities(): void
    {
        $calculator = new StrandPerformanceCalculator();

        self::assertSame('strand-performance', $calculator->name());
        self::assertSame(CalculatorPriority::NORMAL, $calculator->priority());

        $capabilities = $calculator->capabilities();
        self::assertTrue($capabilities->supportsLevel1);
        self::assertTrue($capabilities->supportsLevel2);
    }

    public function testComputesAFullStrandSummaryWhenResponsesExist(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 20, 80, 60),
            [],
            [new StrandAnalyticsRecord(10, 'ST-A', 'MATH', 20, 80, 60)],
            [],
            [],
        );

        $result = (new StrandPerformanceCalculator())->calculate($this->makeExecutionContext($context));

        self::assertSame([], $result->warnings());
        self::assertCount(1, $result->records());

        /** @var StrandResult $record */
        $record = $result->records()[0];
        self::assertSame(10, $record->strandId);
        self::assertSame('ST-A', $record->strandCode);
        self::assertSame('MATH', $record->subjectCode);
        self::assertSame(0.75, $record->percentCorrect);
        self::assertSame(20, $record->studentCount);
        self::assertSame(80, $record->responseCount);
        self::assertSame(60, $record->correctCount);
    }

    public function testAStrandWithNoResponsesProducesANullPercentCorrectAndAWarning(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 0, 0, 0),
            [],
            [new StrandAnalyticsRecord(10, 'ST-A', 'MATH', 0, 0, 0)],
            [],
            [],
        );

        $result = (new StrandPerformanceCalculator())->calculate($this->makeExecutionContext($context));

        self::assertCount(1, $result->warnings());
        self::assertSame('strand:10', $result->warnings()[0]->identifier);
        self::assertNull($result->records()[0]->percentCorrect);
        self::assertSame(0, $result->records()[0]->studentCount);
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

        $result = (new StrandPerformanceCalculator())->calculate($this->makeExecutionContext($context));

        self::assertSame([], $result->records());
        self::assertSame([], $result->warnings());
    }
}
