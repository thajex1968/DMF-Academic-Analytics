<?php

declare(strict_types=1);

namespace DMF\Analytics\Result;

/**
 * One subject's performance summary (SubjectSummary). `percentCorrect` is
 * computable directly from SubjectAnalyticsRecord's pooled counts;
 * `average`/`highest`/`lowest`/`distribution` are `null` whenever the
 * current Canonical Analytics Model does not carry a per-student score
 * series at subject grain — see SubjectPerformanceCalculator's
 * AnalyticsWarning for why, rather than throwing.
 */
final class SubjectResult
{
    /** @param array<string, int>|null $distribution */
    public function __construct(
        public readonly string $subjectCode,
        public readonly ?float $percentCorrect,
        public readonly ?float $average,
        public readonly ?float $highest,
        public readonly ?float $lowest,
        public readonly ?array $distribution,
    ) {
    }
}
