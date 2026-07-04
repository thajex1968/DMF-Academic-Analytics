<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

use DateTimeImmutable;

/**
 * The full, source-independent Dashboard payload — everything a Dashboard
 * Data API action needs to answer any of its narrower views from. Pure
 * data: no HTML, no Bootstrap, no Chart.js.
 */
final class DashboardResponse
{
    /**
     * @param DashboardAssessment[] $assessments
     * @param DashboardSubject[] $subjects
     * @param DashboardStandard[] $standards
     * @param DashboardStrand[] $strands
     * @param DashboardBenchmark[] $benchmarks
     * @param DashboardAlert[] $warnings
     */
    public function __construct(
        public readonly DashboardMetadata $metadata,
        public readonly DashboardSummary $summary,
        public readonly array $assessments,
        public readonly array $subjects,
        public readonly array $standards,
        public readonly array $strands,
        public readonly array $benchmarks,
        public readonly array $warnings,
        public readonly DateTimeImmutable $generationTime,
    ) {
    }
}
