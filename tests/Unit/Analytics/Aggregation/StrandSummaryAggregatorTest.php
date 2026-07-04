<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Aggregation;

use DMF\Analytics\Aggregation\StrandSummaryAggregator;
use DMF\Analytics\Result\StrandResult;
use PHPUnit\Framework\TestCase;

final class StrandSummaryAggregatorTest extends TestCase
{
    public function testReshapesEachStrandResultIntoADashboardStrand(): void
    {
        $results = [new StrandResult(10, 'ST-A', 'MATH', 0.75, 20, 80, 60)];

        $strands = (new StrandSummaryAggregator())->aggregate($results);

        self::assertCount(1, $strands);
        self::assertSame(10, $strands[0]->strandId);
        self::assertSame(0.75, $strands[0]->percentCorrect);
    }

    public function testAnEmptyInputProducesAnEmptyOutput(): void
    {
        self::assertSame([], (new StrandSummaryAggregator())->aggregate([]));
    }
}
