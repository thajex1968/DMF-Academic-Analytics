<?php

declare(strict_types=1);

namespace DMF\Analytics\Canonical;

/**
 * Canonical, whole-assessment evidence tally. Raw counts only — no
 * percentage, index, or other derived statistic is computed here; that is
 * an Analytics Calculator's job (not yet built in this Sprint).
 */
final class AssessmentAnalyticsRecord
{
    public function __construct(
        public readonly int $assessmentId,
        public readonly int $studentCount,
        public readonly int $responseCount,
        public readonly int $correctCount,
    ) {
    }
}
