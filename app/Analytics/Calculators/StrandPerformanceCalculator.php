<?php

declare(strict_types=1);

namespace DMF\Analytics\Calculators;

use DMF\Analytics\Contracts\AnalyticsCalculatorInterface;
use DMF\Analytics\Contracts\AnalyticsResultInterface;
use DMF\Analytics\Contracts\CalculatorCapabilities;
use DMF\Analytics\Contracts\CalculatorExecutionContext;
use DMF\Analytics\Contracts\CalculatorPriority;
use DMF\Analytics\Result\AnalyticsResult;
use DMF\Analytics\Result\AnalyticsWarning;
use DMF\Analytics\Result\StrandResult;

/**
 * Per-learning-strand performance summary (StrandSummary) — pure data, no
 * visualization shaping. Every field is directly computable from
 * StrandAnalyticsRecord's own pooled counts; `percentCorrect` is `null`
 * only when `responseCount` is zero.
 */
final class StrandPerformanceCalculator implements AnalyticsCalculatorInterface
{
    public function name(): string
    {
        return 'strand-performance';
    }

    public function priority(): CalculatorPriority
    {
        return CalculatorPriority::NORMAL;
    }

    public function capabilities(): CalculatorCapabilities
    {
        return new CalculatorCapabilities(true, true);
    }

    public function calculate(CalculatorExecutionContext $executionContext): AnalyticsResultInterface
    {
        $context = $executionContext->context;
        $records = [];
        $warnings = [];

        foreach ($context->strandRecords as $strand) {
            $percentCorrect = null;

            if ($strand->responseCount === 0) {
                $warnings[] = new AnalyticsWarning(
                    "strand:{$strand->strandId}",
                    'No responses recorded for this strand; percent-correct is undefined.',
                );
            } else {
                $percentCorrect = $strand->correctCount / $strand->responseCount;
            }

            $records[] = new StrandResult(
                $strand->strandId,
                $strand->strandCode,
                $strand->subjectCode,
                $percentCorrect,
                $strand->studentCount,
                $strand->responseCount,
                $strand->correctCount,
            );
        }

        return AnalyticsResult::build($this->name(), $records, $warnings, [], $executionContext->executedAt);
    }
}
