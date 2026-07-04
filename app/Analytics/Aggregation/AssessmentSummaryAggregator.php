<?php

declare(strict_types=1);

namespace DMF\Analytics\Aggregation;

use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Dashboard\DashboardAssessment;

/**
 * Builds the Dashboard-ready assessment summary directly from
 * AnalyticsContext.assessmentRecord — no calculator produces an
 * assessment-grain result, so this aggregator derives percent-correct
 * itself from the same pooled counts the calculators already use
 * identically at every other grain.
 */
final class AssessmentSummaryAggregator
{
    public function aggregate(AnalyticsContext $context): DashboardAssessment
    {
        $record = $context->assessmentRecord;
        $percentCorrect = $record->responseCount > 0 ? $record->correctCount / $record->responseCount : null;

        return new DashboardAssessment(
            $record->assessmentId,
            $record->studentCount,
            $record->responseCount,
            $record->correctCount,
            $percentCorrect,
        );
    }
}
