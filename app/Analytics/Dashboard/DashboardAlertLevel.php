<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

/** How serious a DashboardAlert is. */
enum DashboardAlertLevel: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';
}
