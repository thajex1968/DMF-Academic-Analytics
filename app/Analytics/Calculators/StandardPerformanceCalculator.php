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
use DMF\Analytics\Result\StandardResult;

/**
 * Per-learning-standard performance summary. `percentCorrect` is always
 * computed when responses exist (StandardAnalyticsRecord's own pooled
 * counts are sufficient). Mean/median/min/max/standard deviation need a
 * per-student score distribution at standard grain that the current
 * Canonical Analytics Model does not carry — rather than fabricate one or
 * throw, this calculator reports that gap as an AnalyticsWarning and leaves
 * those five fields `null`.
 */
final class StandardPerformanceCalculator implements AnalyticsCalculatorInterface
{
    public function name(): string
    {
        return 'standard-performance';
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

        foreach ($context->standardRecords as $standard) {
            if ($standard->responseCount === 0) {
                $warnings[] = new AnalyticsWarning(
                    "standard:{$standard->standardId}",
                    'No responses recorded for this standard; percent-correct, mean, median, min, max, '
                        . 'and standard deviation are all undefined.',
                );
                $records[] = new StandardResult(
                    $standard->standardId,
                    $standard->standardCode,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                );

                continue;
            }

            $warnings[] = new AnalyticsWarning(
                "standard:{$standard->standardId}",
                'Mean, median, min, max, and standard deviation require a per-student score distribution '
                    . 'the current Canonical Analytics Model does not carry at standard grain; '
                    . 'only percent-correct is available.',
            );

            $records[] = new StandardResult(
                $standard->standardId,
                $standard->standardCode,
                $standard->correctCount / $standard->responseCount,
                null,
                null,
                null,
                null,
                null,
            );
        }

        return AnalyticsResult::build($this->name(), $records, $warnings, [], $executionContext->executedAt);
    }
}
