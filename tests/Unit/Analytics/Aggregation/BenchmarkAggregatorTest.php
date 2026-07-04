<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Aggregation;

use DMF\Analytics\Aggregation\BenchmarkAggregator;
use DMF\Analytics\Canonical\BenchmarkScope;
use DMF\Analytics\Result\BenchmarkResult;
use PHPUnit\Framework\TestCase;

final class BenchmarkAggregatorTest extends TestCase
{
    public function testReshapesEachBenchmarkResultIntoADashboardBenchmark(): void
    {
        $results = [new BenchmarkResult(BenchmarkScope::COUNTRY, 'MATH', 0.8, 0.7, 0.1)];

        $benchmarks = (new BenchmarkAggregator())->aggregate($results);

        self::assertCount(1, $benchmarks);
        self::assertSame(BenchmarkScope::COUNTRY, $benchmarks[0]->scope);
        self::assertSame(0.1, $benchmarks[0]->difference);
    }

    public function testAnEmptyInputProducesAnEmptyOutput(): void
    {
        self::assertSame([], (new BenchmarkAggregator())->aggregate([]));
    }
}
