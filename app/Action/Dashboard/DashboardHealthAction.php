<?php

declare(strict_types=1);

namespace DMF\Action\Dashboard;

use DMF\Analytics\Aggregation\DashboardHealthAggregator;
use DMF\Analytics\Dashboard\DashboardResponseSerializer;
use Dmf\Core\Http\Request;
use Dmf\Core\Http\Response;

/**
 * `GET action=dashboard_health` (Sprint 4 Phase 3, Module 7) — read-only
 * operational snapshot. No database repair, no admin action.
 */
final class DashboardHealthAction
{
    public function __construct(
        private readonly DashboardHealthAggregator $health,
        private readonly DashboardResponseSerializer $serializer,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        return Response::ok($this->serializer->health($this->health->build()));
    }
}
