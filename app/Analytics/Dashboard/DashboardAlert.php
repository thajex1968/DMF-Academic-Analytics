<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

/**
 * A dashboard-level notice — built from an AnalyticsWarning/AnalyticsIssue
 * a calculator produced, or from a health-check finding. Plain data; no
 * HTML, no icon/color mapping (that is a frontend concern).
 */
final class DashboardAlert
{
    public function __construct(
        public readonly DashboardAlertLevel $level,
        public readonly string $identifier,
        public readonly string $message,
    ) {
    }
}
