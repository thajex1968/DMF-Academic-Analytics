<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

/** Dashboard-ready per-standard figures — mirrors Result\StandardResult. */
final class DashboardStandard
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
