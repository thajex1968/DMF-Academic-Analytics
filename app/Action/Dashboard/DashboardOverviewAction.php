<?php

declare(strict_types=1);

namespace DMF\Action\Dashboard;

use DMF\Analytics\Aggregation\AnalyticsAggregationService;
use DMF\Analytics\Dashboard\DashboardResponseSerializer;
use Dmf\Core\Http\Request;
use Dmf\Core\Http\Response;

/**
 * `GET action=dashboard_overview` (Sprint 4 Phase 3, decisions/IDR-011) —
 * the full DashboardResponse: metadata, summary, assessments, subjects,
 * standards, strands, benchmarks, warnings. Never calculates anything
 * itself — consumes only `AnalyticsAggregationService`'s already-aggregated
 * result (Architecture Rules: Actions must never calculate analytics).
 */
final class DashboardOverviewAction
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

        return Response::ok($this->serializer->response($response));
    }
}
