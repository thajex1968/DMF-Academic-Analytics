<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Calculators;

use DateTimeImmutable;
use DMF\Analytics\Calculators\BenchmarkCalculator;
use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Canonical\AssessmentAnalyticsRecord;
use DMF\Analytics\Canonical\BenchmarkAnalyticsRecord;
use DMF\Analytics\Canonical\BenchmarkScope;
use DMF\Analytics\Canonical\SubjectAnalyticsRecord;
use DMF\Analytics\Contracts\CalculatorExecutionContext;
use DMF\Analytics\Contracts\CalculatorPriority;
use DMF\Analytics\Result\BenchmarkResult;
use PHPUnit\Framework\TestCase;

final class BenchmarkCalculatorTest extends TestCase
{
    private function makeExecutionContext(AnalyticsContext $context): CalculatorExecutionContext
    {
        return new CalculatorExecutionContext($context, new DateTimeImmutable('2026-07-04T09:00:00+07:00'));
    }

    public function testNamePriorityAndCapabilities(): void
    {
        $calculator = new BenchmarkCalculator();

        self::assertSame('benchmark', $calculator->name());
        self::assertSame(CalculatorPriority::LOW, $calculator->priority());

        $capabilities = $calculator->capabilities();
        self::assertTrue($capabilities->supportsLevel1);
        self::assertFalse($capabilities->supportsLevel2);
    }

    public function testComparesTheSchoolsOwnPercentCorrectAgainstEachSuppliedBenchmark(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 30, 100, 80),
            [new SubjectAnalyticsRecord('MATH', 30, 100, 80)],
            [],
            [],
            [],
            [
                new BenchmarkAnalyticsRecord(BenchmarkScope::PROVINCE, 'MATH', 0.72),
                new BenchmarkAnalyticsRecord(BenchmarkScope::COUNTRY, 'MATH', 0.85),
            ],
        );

        $result = (new BenchmarkCalculator())->calculate($this->makeExecutionContext($context));

        self::assertSame([], $result->warnings());
        self::assertCount(2, $result->records());

        /** @var BenchmarkResult[] $records */
        $records = $result->records();
        self::assertSame(BenchmarkScope::PROVINCE, $records[0]->scope);
        self::assertSame(0.8, $records[0]->schoolPercentCorrect);
        self::assertSame(0.72, $records[0]->benchmarkPercentCorrect);
        self::assertEqualsWithDelta(0.08, $records[0]->difference, 0.0001);

        self::assertSame(BenchmarkScope::COUNTRY, $records[1]->scope);
        self::assertEqualsWithDelta(-0.05, $records[1]->difference, 0.0001);
    }

    public function testABenchmarkForASubjectTheSchoolHasNoDataForProducesAWarningNotAnException(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 0, 0, 0),
            [],
            [],
            [],
            [],
            [new BenchmarkAnalyticsRecord(BenchmarkScope::SCHOOL, 'SCI', 0.5)],
        );

        $result = (new BenchmarkCalculator())->calculate($this->makeExecutionContext($context));

        self::assertSame([], $result->records());
        self::assertCount(1, $result->warnings());
        self::assertSame('benchmark:school:SCI', $result->warnings()[0]->identifier);
    }

    public function testNoBenchmarkRecordsProducesAnEmptyResultWithNoWarnings(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 0, 0, 0),
            [new SubjectAnalyticsRecord('MATH', 30, 100, 80)],
            [],
            [],
            [],
        );

        $result = (new BenchmarkCalculator())->calculate($this->makeExecutionContext($context));

        self::assertSame([], $result->records());
        self::assertSame([], $result->warnings());
    }
}
