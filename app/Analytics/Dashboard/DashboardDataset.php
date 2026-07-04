<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

/**
 * A named series of labeled numeric points (e.g. subject code → percent
 * correct) — generic enough for a future frontend to turn into whichever
 * chart it wants. Never Chart.js-shaped, never HTML.
 */
final class DashboardDataset
{
    /** @param array<string, float|null> $points */
    public function __construct(
        public readonly string $label,
        public readonly array $points,
    ) {
    }
}
