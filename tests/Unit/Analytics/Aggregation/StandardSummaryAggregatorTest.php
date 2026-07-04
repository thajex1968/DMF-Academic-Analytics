<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Aggregation;

use DMF\Analytics\Aggregation\StandardSummaryAggregator;
use DMF\Analytics\Result\StandardResult;
use PHPUnit\Framework\TestCase;

final class StandardSummaryAggregatorTest extends TestCase
{
    public function testReshapesEachStandardResultIntoADashboardStandard(): void
    {
        $results = [new StandardResult(100, 'STD-A1', 0.6, null, null, null, null, null)];

        $standards = (new StandardSummaryAggregator())->aggregate($results);

        self::assertCount(1, $standards);
        self::assertSame(100, $standards[0]->standardId);
        self::assertSame(0.6, $standards[0]->percentCorrect);
    }

    public function testAnEmptyInputProducesAnEmptyOutput(): void
    {
        self::assertSame([], (new StandardSummaryAggregator())->aggregate([]));
    }
}
