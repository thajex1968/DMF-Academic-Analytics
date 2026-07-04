<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DMF\Analytics\Dashboard\DashboardCard;
use PHPUnit\Framework\TestCase;

final class DashboardCardTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $card = new DashboardCard('Total Students', 120.0, null);

        self::assertSame('Total Students', $card->label);
        self::assertSame(120.0, $card->value);
        self::assertNull($card->unit);
    }

    public function testValueAndUnitAcceptNull(): void
    {
        $card = new DashboardCard('Overall Percent Correct', null, '%');

        self::assertNull($card->value);
        self::assertSame('%', $card->unit);
    }
}
