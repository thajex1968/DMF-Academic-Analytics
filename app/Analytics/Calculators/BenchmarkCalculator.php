<?php

declare(strict_types=1);

namespace DMF\Analytics\Calculators;

use DMF\Analytics\Canonical\SubjectAnalyticsRecord;
use DMF\Analytics\Contracts\AnalyticsCalculatorInterface;
use DMF\Analytics\Contracts\AnalyticsResultInterface;
use DMF\Analytics\Contracts\CalculatorCapabilities;
use DMF\Analytics\Contracts\CalculatorExecutionContext;
use DMF\Analytics\Contracts\CalculatorPriority;
use DMF\Analytics\Result\AnalyticsResult;
use DMF\Analytics\Result\AnalyticsWarning;
use DMF\Analytics\Result\BenchmarkResult;

/**
 * Compares this school's own subject-level percent-correct against
 * whichever BenchmarkAnalyticsRecord values are already present in the
 * AnalyticsContext (school/province/region/country). Never fetches or
 * computes a benchmark value itself — AnalyticsContext.benchmarkRecords is
 * empty until a future Level 1 Assessment Adapter populates it; this
 * calculator only ever reads what is already there.
 */
final class BenchmarkCalculator implements AnalyticsCalculatorInterface
{
    public function name(): string
    {
        return 'benchmark';
    }

    public function priority(): CalculatorPriority
    {
        return CalculatorPriority::LOW;
    }

    public function capabilities(): CalculatorCapabilities
    {
        return new CalculatorCapabilities(true, false);
    }

    public function calculate(CalculatorExecutionContext $executionContext): AnalyticsResultInterface
    {
        $context = $executionContext->context;

        /** @var array<string, SubjectAnalyticsRecord> $subjectByCode */
        $subjectByCode = [];
        foreach ($context->subjectRecords as $subject) {
            $subjectByCode[$subject->subjectCode] = $subject;
        }

        $records = [];
        $warnings = [];

        foreach ($context->benchmarkRecords as $benchmark) {
            $subject = $subjectByCode[$benchmark->subjectCode] ?? null;

            if ($subject === null || $subject->responseCount === 0) {
                $warnings[] = new AnalyticsWarning(
                    "benchmark:{$benchmark->scope->value}:{$benchmark->subjectCode}",
                    'No school-level data available for this subject; benchmark comparison is undefined.',
                );

                continue;
            }

            $schoolPercentCorrect = $subject->correctCount / $subject->responseCount;

            $records[] = new BenchmarkResult(
                $benchmark->scope,
                $benchmark->subjectCode,
                $schoolPercentCorrect,
                $benchmark->comparisonValue,
                $schoolPercentCorrect - $benchmark->comparisonValue,
            );
        }

        return AnalyticsResult::build($this->name(), $records, $warnings, [], $executionContext->executedAt);
    }
}
