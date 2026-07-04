<?php

declare(strict_types=1);

namespace DMF\Analytics\Canonical;

/**
 * One externally-published comparison figure for one subject at one
 * BenchmarkScope tier (RFC-004's evidenced O-NET comparison tiers —
 * school-size, location, province, region, country). Populated only by a
 * future Assessment Adapter for a Level 1 source that publishes such
 * comparisons; no adapter exists yet, so nothing in this Sprint ever
 * constructs one outside a test — BenchmarkCalculator must still only ever
 * read this, never fetch or compute it itself.
 */
final class BenchmarkAnalyticsRecord
{
    public function __construct(
        public readonly BenchmarkScope $scope,
        public readonly string $subjectCode,
        public readonly float $comparisonValue,
    ) {
    }
}
