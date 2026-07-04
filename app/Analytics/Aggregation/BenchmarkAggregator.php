<?php

declare(strict_types=1);

namespace DMF\Analytics\Aggregation;

use DMF\Analytics\Dashboard\DashboardBenchmark;
use DMF\Analytics\Result\BenchmarkResult;

/** Reshapes BenchmarkCalculator's output into Dashboard-ready DTOs. No new computation. */
final class BenchmarkAggregator
{
    /**
     * @param BenchmarkResult[] $results
     * @return DashboardBenchmark[]
     */
    public function aggregate(array $results): array
    {
        return array_map(
            static fn (BenchmarkResult $result): DashboardBenchmark => new DashboardBenchmark(
                $result->scope,
                $result->subjectCode,
                $result->schoolPercentCorrect,
                $result->benchmarkPercentCorrect,
                $result->difference,
            ),
            $results,
        );
    }
}
