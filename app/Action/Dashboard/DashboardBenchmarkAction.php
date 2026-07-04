<?php

declare(strict_types=1);

namespace DMF\Action\Dashboard;

use DMF\Analytics\Aggregation\AnalyticsAggregationService;
use DMF\Analytics\Dashboard\DashboardResponseSerializer;
use Dmf\Core\Http\Request;
use Dmf\Core\Http\Response;

/**
 * `GET action=dashboard_benchmark` (Sprint 4 Phase 3, decisions/IDR-011) —
 * metadata + benchmarks only. Empty today in any real deployment — no
 * Level 1 Assessment Adapter yet populates AnalyticsContext.benchmarkRecords
 * (decisions/IDR-011 §7) — not a defect, an honest empty result.
 */
final class DashboardBenchmarkAction
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
            'benchmarks' => array_map($this->serializer->benchmark(...), $response->benchmarks),
            'generation_time' => $response->generationTime->format(DATE_ATOM),
        ]);
    }
}
