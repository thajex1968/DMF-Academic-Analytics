<?php

declare(strict_types=1);

namespace DMF\Analytics\Canonical;

/**
 * Canonical evidence tally rolled up to มาตรฐาน (Learning Standard) grain.
 * Raw counts only — see AssessmentAnalyticsRecord for why no derived
 * statistic lives here.
 */
final class StandardAnalyticsRecord
{
    public function __construct(
        public readonly int $standardId,
        public readonly string $standardCode,
        public readonly int $strandId,
        public readonly int $studentCount,
        public readonly int $responseCount,
        public readonly int $correctCount,
    ) {
    }
}
