<?php

declare(strict_types=1);

namespace DMF\Analytics\Aggregation;

use DMF\Analytics\Dashboard\DashboardSubject;
use DMF\Analytics\Result\SubjectResult;

/** Reshapes SubjectPerformanceCalculator's output into Dashboard-ready DTOs. No new computation. */
final class SubjectSummaryAggregator
{
    /**
     * @param SubjectResult[] $results
     * @return DashboardSubject[]
     */
    public function aggregate(array $results): array
    {
        return array_map(
            static fn (SubjectResult $result): DashboardSubject => new DashboardSubject(
                $result->subjectCode,
                $result->percentCorrect,
                $result->average,
                $result->highest,
                $result->lowest,
                $result->distribution,
            ),
            $results,
        );
    }
}
