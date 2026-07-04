<?php

declare(strict_types=1);

namespace DMF\Analytics\Aggregation;

use DMF\Analytics\Dashboard\DashboardStandard;
use DMF\Analytics\Result\StandardResult;

/** Reshapes StandardPerformanceCalculator's output into Dashboard-ready DTOs. No new computation. */
final class StandardSummaryAggregator
{
    /**
     * @param StandardResult[] $results
     * @return DashboardStandard[]
     */
    public function aggregate(array $results): array
    {
        return array_map(
            static fn (StandardResult $result): DashboardStandard => new DashboardStandard(
                $result->standardId,
                $result->standardCode,
                $result->percentCorrect,
                $result->mean,
                $result->median,
                $result->min,
                $result->max,
                $result->standardDeviation,
            ),
            $results,
        );
    }
}
