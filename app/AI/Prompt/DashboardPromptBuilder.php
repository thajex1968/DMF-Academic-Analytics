<?php

declare(strict_types=1);

namespace DMF\AI\Prompt;

use DMF\AI\Contracts\PromptBuilderInterface;
use DMF\AI\DTO\AIContext;
use DMF\Analytics\Dashboard\DashboardAssessment;
use DMF\Analytics\Dashboard\DashboardHealth;
use DMF\Analytics\Dashboard\DashboardSummary;

/**
 * The one concrete PromptBuilderInterface this phase ships. Reads only the
 * three Dashboard DTOs it is given — never a repository, entity, SQL, PDO
 * handle, database row, or raw array. Renders "not available" honestly for
 * any `null` field rather than omitting it silently, so a reader (human or
 * AI provider) always knows a gap is a gap, not an oversight.
 */
final class DashboardPromptBuilder implements PromptBuilderInterface
{
    public function build(
        PromptType $type,
        AIContext $context,
        DashboardSummary $summary,
        DashboardHealth $health,
        DashboardAssessment $assessment,
    ): Prompt {
        return new Prompt(
            $type,
            $this->buildContext($context),
            $this->buildStatistics($summary, $assessment),
            $this->buildInsights($health),
            $this->buildRequiredOutput($type),
            $this->buildSafetyRules(),
        );
    }

    private function buildContext(AIContext $context): string
    {
        return sprintf(
            "Assessment ID: %d\nSchool ID: %d\nLanguage: %s\nTimezone: %s",
            $context->assessmentId,
            $context->schoolId,
            $context->language,
            $context->timezone,
        );
    }

    private function buildStatistics(DashboardSummary $summary, DashboardAssessment $assessment): string
    {
        $lines = [
            'Total students: ' . $assessment->studentCount,
            'Total responses: ' . $assessment->responseCount,
            'Correct responses: ' . $assessment->correctCount,
            'Overall percent correct: ' . $this->formatNullablePercent($summary->overallPercentCorrect),
        ];

        return implode("\n", $lines);
    }

    private function buildInsights(DashboardHealth $health): string
    {
        $lines = [
            'Import status: ' . $health->importStatus,
            'Analytics status: ' . $health->analyticsStatus,
        ];

        if ($health->warnings === []) {
            $lines[] = 'Warnings: none';
        } else {
            foreach ($health->warnings as $warning) {
                $lines[] = 'Warning (' . $warning->level->value . '): ' . $warning->message;
            }
        }

        return implode("\n", $lines);
    }

    private function buildRequiredOutput(PromptType $type): string
    {
        if ($type === PromptType::INSIGHT) {
            return 'Return a JSON object with exactly these keys: '
                . '"summary" (string), "strengths" (string array), "weaknesses" (string array), '
                . '"risks" (string array), "confidence" (number between 0 and 1).';
        }

        return 'Return a JSON object with exactly these keys: '
            . '"priority" (one of "high", "medium", "low"), "category" (string), '
            . '"recommendation" (string), "reason" (string), "confidence" (number between 0 and 1).';
    }

    private function buildSafetyRules(): string
    {
        return implode("\n", [
            'Never fabricate a number that is not present in the Statistics section above.',
            'If a required statistic is missing or "not available", state "Insufficient data" '
                . 'rather than guessing.',
            'Never invent a benchmark, difficulty, or discrimination value — none is provided here.',
            'Never produce a recommendation based on a value that does not appear above.',
        ]);
    }

    private function formatNullablePercent(?float $value): string
    {
        return $value === null ? 'not available' : sprintf('%.1f%%', $value * 100);
    }
}
