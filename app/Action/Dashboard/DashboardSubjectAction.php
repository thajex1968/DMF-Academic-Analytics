<?php

declare(strict_types=1);

namespace DMF\Action\Dashboard;

use DMF\Analytics\Aggregation\AnalyticsAggregationService;
use DMF\Analytics\Dashboard\DashboardResponseSerializer;
use Dmf\Core\Http\Request;
use Dmf\Core\Http\Response;

/**
 * `GET action=dashboard_subjects` (Sprint 4 Phase 3, decisions/IDR-011) —
 * the "by subject" drill-down: metadata + subjects + the standards/strands
 * that only make sense nested under a subject. Never calculates anything
 * itself.
 */
final class DashboardSubjectAction
{
    public function __construct(
        private readonly AnalyticsAggregationService $aggregation,
        private readonly DashboardResponseSerializer $serializer,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $response = $this->aggregation->forLatestAssessment();

        if ($response === null) {
            return Response::ok(['message' => 'No assessment has been registered yet.']);
        }

        return Response::ok([
            'metadata' => $this->serializer->metadata($response->metadata),
            'subjects' => array_map($this->serializer->subject(...), $response->subjects),
            'strands' => array_map($this->serializer->strand(...), $response->strands),
            'standards' => array_map($this->serializer->standard(...), $response->standards),
            'generation_time' => $response->generationTime->format(DATE_ATOM),
        ]);
    }
}
