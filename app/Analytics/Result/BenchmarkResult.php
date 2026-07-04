<?php

declare(strict_types=1);

namespace DMF\Analytics\Result;

use DMF\Analytics\Canonical\BenchmarkScope;

/**
 * One subject's school-level percent-correct compared against an
 * externally-published BenchmarkAnalyticsRecord at one comparison tier.
 */
final class BenchmarkResult
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
