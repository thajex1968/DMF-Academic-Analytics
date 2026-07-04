<?php

declare(strict_types=1);

namespace DMF\Analytics\Canonical;

/**
 * Canonical, per-question evidence tally. `standardId` is the question's
 * primary standard only — indicator grain is deliberately not modeled here,
 * per RFC-004's still-open indicator-vs-standard grain question
 * (docs/03-Database-Design.md §9, `standard_performance_summary`). Raw
 * counts only — see AssessmentAnalyticsRecord for why no derived statistic
 * lives here.
 */
final class QuestionAnalyticsRecord
{
    public function __construct(
        public readonly int $questionId,
        public readonly int $standardId,
        public readonly int $studentCount,
        public readonly int $responseCount,
        public readonly int $correctCount,
    ) {
    }
}
