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
use DMF\Analytics\Result\SubjectResult;

/**
 * Per-subject performance summary (SubjectSummary). `percentCorrect` is
 * always computed when responses exist (SubjectAnalyticsRecord's own
 * pooled counts are sufficient). Average/highest/lowest/distribution need a
 * per-student score series at subject grain that the current Canonical
 * Analytics Model does not carry — rather than fabricate one or throw, this
 * calculator reports that gap as an AnalyticsWarning and leaves those four
 * fields `null`.
 */
final class SubjectPerformanceCalculator implements AnalyticsCalculatorInterface
{
    public function name(): string
    {
        return 'subject-performance';
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

        foreach ($context->subjectRecords as $subject) {
            if ($subject->responseCount === 0) {
                $warnings[] = new AnalyticsWarning(
                    "subject:{$subject->subjectCode}",
                    'No responses recorded for this subject; percent-correct, average, highest, lowest, '
                        . 'and distribution are all undefined.',
                );
                $records[] = new SubjectResult($subject->subjectCode, null, null, null, null, null);

                continue;
            }

            $warnings[] = new AnalyticsWarning(
                "subject:{$subject->subjectCode}",
                'Average, highest, lowest, and distribution require a per-student score series '
                    . 'the current Canonical Analytics Model does not carry at subject grain; '
                    . 'only percent-correct is available.',
            );

            $records[] = new SubjectResult(
                $subject->subjectCode,
                $subject->correctCount / $subject->responseCount,
                null,
                null,
                null,
                null,
            );
        }

        return AnalyticsResult::build($this->name(), $records, $warnings, [], $executionContext->executedAt);
    }
}
