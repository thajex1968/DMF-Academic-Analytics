<?php

declare(strict_types=1);

namespace DMF\Analytics\Canonical;

/**
 * Canonical evidence tally rolled up to สาระ (Learning Strand) grain. Raw
 * counts only — see AssessmentAnalyticsRecord for why no derived statistic
 * lives here.
 */
final class StrandAnalyticsRecord
{
    public function __construct(
        public readonly int $strandId,
        public readonly string $strandCode,
        public readonly string $subjectCode,
        public readonly int $studentCount,
        public readonly int $responseCount,
        public readonly int $correctCount,
    ) {
    }
}
