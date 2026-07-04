<?php

declare(strict_types=1);

namespace DMF\Analytics\Result;

/**
 * One learning standard's performance summary. `percentCorrect` is
 * computable directly from StandardAnalyticsRecord's pooled counts;
 * `mean`/`median`/`min`/`max`/`standardDeviation` are `null` whenever the
 * current Canonical Analytics Model does not carry a per-student score
 * distribution at standard grain — see StandardPerformanceCalculator's
 * AnalyticsWarning for why, rather than throwing.
 */
final class StandardResult
{
    public function __construct(
        public readonly int $standardId,
        public readonly string $standardCode,
        public readonly ?float $percentCorrect,
        public readonly ?float $mean,
        public readonly ?float $median,
        public readonly ?float $min,
        public readonly ?float $max,
        public readonly ?float $standardDeviation,
    ) {
    }
}
