<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Calculators;

use DateTimeImmutable;
use DMF\Analytics\Calculators\SubjectPerformanceCalculator;
use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Canonical\AssessmentAnalyticsRecord;
use DMF\Analytics\Canonical\SubjectAnalyticsRecord;
use DMF\Analytics\Contracts\CalculatorExecutionContext;
use DMF\Analytics\Contracts\CalculatorPriority;
use DMF\Analytics\Result\SubjectResult;
use PHPUnit\Framework\TestCase;

final class SubjectPerformanceCalculatorTest extends TestCase
{
    private function makeExecutionContext(AnalyticsContext $context): CalculatorExecutionContext
    {
        return new CalculatorExecutionContext($context, new DateTimeImmutable('2026-07-04T09:00:00+07:00'));
    }

    public function testNamePriorityAndCapabilities(): void
    {
        $calculator = new SubjectPerformanceCalculator();

        self::assertSame('subject-performance', $calculator->name());
        self::assertSame(CalculatorPriority::NORMAL, $calculator->priority());

        $capabilities = $calculator->capabilities();
        self::assertTrue($capabilities->supportsLevel1);
        self::assertTrue($capabilities->supportsLevel2);
    }

    public function testComputesPercentCorrectButWarnsThatFourStatisticsAreUnavailable(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 30, 100, 65),
            [new SubjectAnalyticsRecord('MATH', 30, 100, 65)],
            [],
            [],
            [],
        );

        $result = (new SubjectPerformanceCalculator())->calculate($this->makeExecutionContext($context));

        self::assertCount(1, $result->records());
        self::assertCount(1, $result->warnings());
        self::assertSame('subject:MATH', $result->warnings()[0]->identifier);

        /** @var SubjectResult $record */
        $record = $result->records()[0];
        self::assertSame('MATH', $record->subjectCode);
        self::assertSame(0.65, $record->percentCorrect);
        self::assertNull($record->average);
        self::assertNull($record->highest);
        self::assertNull($record->lowest);
        self::assertNull($record->distribution);
    }

    public function testASubjectWithNoResponsesProducesAllNullFieldsAndOneCombinedWarning(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 0, 0, 0),
            [new SubjectAnalyticsRecord('MATH', 0, 0, 0)],
            [],
            [],
            [],
        );

        $result = (new SubjectPerformanceCalculator())->calculate($this->makeExecutionContext($context));

        self::assertCount(1, $result->records());
        self::assertCount(1, $result->warnings());
        self::assertNull($result->records()[0]->percentCorrect);
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

        $result = (new SubjectPerformanceCalculator())->calculate($this->makeExecutionContext($context));

        self::assertSame([], $result->records());
        self::assertSame([], $result->warnings());
    }
}
