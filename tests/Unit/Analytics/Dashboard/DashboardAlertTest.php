<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DMF\Analytics\Dashboard\DashboardAlert;
use DMF\Analytics\Dashboard\DashboardAlertLevel;
use PHPUnit\Framework\TestCase;

final class DashboardAlertTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $alert = new DashboardAlert(DashboardAlertLevel::WARNING, 'question:1001', 'No responses recorded.');

        self::assertSame(DashboardAlertLevel::WARNING, $alert->level);
        self::assertSame('question:1001', $alert->identifier);
        self::assertSame('No responses recorded.', $alert->message);
    }
}
