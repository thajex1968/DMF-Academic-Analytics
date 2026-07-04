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
use DMF\Analytics\Result\DifficultyResult;

/**
 * CTT difficulty index (p-value) per question. Reads only
 * QuestionAnalyticsRecord's pooled `correctCount`/`responseCount` — the
 * same pooled shape regardless of whether a Level 1 Assessment Adapter
 * upserted them directly from an already-published figure, or a Level 2
 * Adapter accumulated them from raw responses (docs/03-Database-Design.md
 * §14). This calculator never inspects which path produced them.
 */
final class DifficultyCalculator implements AnalyticsCalculatorInterface
{
    public function name(): string
    {
        return 'difficulty';
    }

    public function priority(): CalculatorPriority
    {
        return CalculatorPriority::HIGH;
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

        foreach ($context->questionRecords as $question) {
            if ($question->responseCount === 0) {
                $warnings[] = new AnalyticsWarning(
                    "question:{$question->questionId}",
                    'No responses recorded for this question; difficulty index is undefined.',
                );

                continue;
            }

            $records[] = new DifficultyResult(
                $question->questionId,
                $question->standardId,
                $question->correctCount / $question->responseCount,
            );
        }

        return AnalyticsResult::build($this->name(), $records, $warnings, [], $executionContext->executedAt);
    }
}
