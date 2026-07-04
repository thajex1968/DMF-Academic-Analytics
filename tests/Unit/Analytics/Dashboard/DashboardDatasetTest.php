<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DMF\Analytics\Dashboard\DashboardDataset;
use PHPUnit\Framework\TestCase;

final class DashboardDatasetTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $points = ['MATH' => 0.7, 'SCI' => 0.65];

        $dataset = new DashboardDataset('Subject Percent Correct', $points);

        self::assertSame('Subject Percent Correct', $dataset->label);
        self::assertSame($points, $dataset->points);
    }
}
