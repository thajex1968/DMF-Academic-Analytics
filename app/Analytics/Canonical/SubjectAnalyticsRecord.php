<?php

declare(strict_types=1);

namespace DMF\Analytics\Canonical;

/**
 * Canonical evidence tally rolled up to whole-subject grain. Raw counts
 * only — see AssessmentAnalyticsRecord for why no derived statistic lives
 * here.
 */
final class SubjectAnalyticsRecord
{
    public function __construct(
        public readonly string $subjectCode,
        public readonly int $studentCount,
        public readonly int $responseCount,
        public readonly int $correctCount,
    ) {
    }
}
