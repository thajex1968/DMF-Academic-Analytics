<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

/** The overview's top-level KPI summary — pure data, no dashboard formatting. */
final class DashboardSummary
{
    /**
     * @param DashboardCard[] $cards
     * @param DashboardDataset[] $datasets
     */
    public function __construct(
        public readonly ?float $overallPercentCorrect,
        public readonly int $totalStudents,
        public readonly int $totalResponses,
        public readonly array $cards,
        public readonly array $datasets,
    ) {
    }
}
