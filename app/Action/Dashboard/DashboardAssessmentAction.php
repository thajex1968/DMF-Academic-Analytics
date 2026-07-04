<?php

declare(strict_types=1);

namespace DMF\Action\Dashboard;

use DMF\Analytics\Aggregation\AnalyticsAggregationService;
use DMF\Analytics\Dashboard\DashboardResponseSerializer;
use Dmf\Core\Http\Request;
use Dmf\Core\Http\Response;

/**
 * `GET action=dashboard_assessment` (Sprint 4 Phase 3, decisions/IDR-011) —
 * a focused, assessment-grain KPI view: metadata + assessments only. Never
 * calculates anything itself.
 */
final class DashboardAssessmentAction
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
            'assessments' => array_map($this->serializer->assessment(...), $response->assessments),
            'generation_time' => $response->generationTime->format(DATE_ATOM),
        ]);
    }
}
