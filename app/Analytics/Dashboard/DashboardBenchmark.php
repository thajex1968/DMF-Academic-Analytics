<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

use DMF\Analytics\Canonical\BenchmarkScope;

/** Dashboard-ready benchmark comparison — mirrors Result\BenchmarkResult. */
final class DashboardBenchmark
{
    public function __construct(
        public readonly BenchmarkScope $scope,
        public readonly string $subjectCode,
        public readonly float $schoolPercentCorrect,
        public readonly float $benchmarkPercentCorrect,
        public readonly float $difference,
    ) {
    }
}
