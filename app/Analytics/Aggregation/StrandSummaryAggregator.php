<?php

declare(strict_types=1);

namespace DMF\Analytics\Aggregation;

use DMF\Analytics\Dashboard\DashboardStrand;
use DMF\Analytics\Result\StrandResult;

/** Reshapes StrandPerformanceCalculator's output into Dashboard-ready DTOs. No new computation. */
final class StrandSummaryAggregator
{
    /**
     * @param StrandResult[] $results
     * @return DashboardStrand[]
     */
    public function aggregate(array $results): array
    {
        return array_map(
            static fn (StrandResult $result): DashboardStrand => new DashboardStrand(
                $result->strandId,
                $result->strandCode,
                $result->subjectCode,
                $result->percentCorrect,
                $result->studentCount,
                $result->responseCount,
                $result->correctCount,
            ),
            $results,
        );
    }
}
