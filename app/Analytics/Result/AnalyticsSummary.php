<?php

declare(strict_types=1);

namespace DMF\Analytics\Result;

use DateTimeImmutable;

/**
 * Aggregate statistics about one calculator's run — counts only, never a
 * formatted or dashboard-ready string.
 */
final class AnalyticsSummary
{
    public function __construct(
        public readonly string $calculatorName,
        public readonly int $recordCount,
        public readonly int $issueCount,
        public readonly int $warningCount,
        public readonly DateTimeImmutable $computedAt,
    ) {
    }
}
