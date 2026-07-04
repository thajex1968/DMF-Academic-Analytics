<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DMF\Analytics\Dashboard\DashboardCard;
use DMF\Analytics\Dashboard\DashboardDataset;
use DMF\Analytics\Dashboard\DashboardSummary;
use PHPUnit\Framework\TestCase;

final class DashboardSummaryTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $cards = [new DashboardCard('Total Students', 30.0, null)];
        $datasets = [new DashboardDataset('Subject Percent Correct', ['MATH' => 0.7])];

        $summary = new DashboardSummary(0.75, 30, 120, $cards, $datasets);

        self::assertSame(0.75, $summary->overallPercentCorrect);
        self::assertSame(30, $summary->totalStudents);
        self::assertSame(120, $summary->totalResponses);
        self::assertSame($cards, $summary->cards);
        self::assertSame($datasets, $summary->datasets);
    }
}
