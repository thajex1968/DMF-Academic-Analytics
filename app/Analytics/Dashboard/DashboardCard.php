<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

/**
 * One dashboard-overview KPI tile — a label, a numeric value, and an
 * optional unit (e.g. `%`). No formatted display string, no color/icon —
 * that shaping belongs to a future frontend, not this layer.
 */
final class DashboardCard
{
    public function __construct(
        public readonly string $label,
        public readonly ?float $value,
        public readonly ?string $unit,
    ) {
    }
}
