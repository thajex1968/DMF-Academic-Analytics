<?php

declare(strict_types=1);

namespace DMF\Analytics\Result;

/**
 * One learning strand's performance summary (StrandSummary) — pure data,
 * no visualization shaping. Everything here is directly computable from
 * StrandAnalyticsRecord's own pooled counts; `percentCorrect` is `null`
 * only when `responseCount` is zero (undefined, not zero).
 */
final class StrandResult
{
    public function __construct(
        public readonly int $strandId,
        public readonly string $strandCode,
        public readonly string $subjectCode,
        public readonly ?float $percentCorrect,
        public readonly int $studentCount,
        public readonly int $responseCount,
        public readonly int $correctCount,
    ) {
    }
}
