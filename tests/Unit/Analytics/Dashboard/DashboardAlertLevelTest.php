<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DMF\Analytics\Dashboard\DashboardAlertLevel;
use PHPUnit\Framework\TestCase;

final class DashboardAlertLevelTest extends TestCase
{
    public function testEveryCaseHasItsExpectedStringValue(): void
    {
        self::assertSame('info', DashboardAlertLevel::INFO->value);
        self::assertSame('warning', DashboardAlertLevel::WARNING->value);
        self::assertSame('critical', DashboardAlertLevel::CRITICAL->value);
    }
}
