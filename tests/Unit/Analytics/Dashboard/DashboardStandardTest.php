<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DMF\Analytics\Dashboard\DashboardStandard;
use PHPUnit\Framework\TestCase;

final class DashboardStandardTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $standard = new DashboardStandard(100, 'STD-A1', 0.6, 0.5, 0.55, 0.1, 0.9, 0.2);

        self::assertSame(100, $standard->standardId);
        self::assertSame('STD-A1', $standard->standardCode);
        self::assertSame(0.6, $standard->percentCorrect);
        self::assertSame(0.5, $standard->mean);
        self::assertSame(0.55, $standard->median);
        self::assertSame(0.1, $standard->min);
        self::assertSame(0.9, $standard->max);
        self::assertSame(0.2, $standard->standardDeviation);
    }

    public function testEveryStatisticFieldAcceptsNull(): void
    {
        $standard = new DashboardStandard(100, 'STD-A1', null, null, null, null, null, null);

        self::assertNull($standard->percentCorrect);
        self::assertNull($standard->mean);
        self::assertNull($standard->standardDeviation);
    }
}
