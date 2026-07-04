<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Action\Dashboard;

use DMF\Action\Dashboard\DashboardOverviewAction;
use DMF\Analytics\Dashboard\DashboardResponseSerializer;
use Dmf\Core\Http\Request;

final class DashboardOverviewActionTest extends DashboardActionTestCase
{
    public function testReturnsTheFullDashboardResponseAsJson(): void
    {
        $action = new DashboardOverviewAction($this->makeServiceWithAssessment(), new DashboardResponseSerializer());

        $response = $action(new Request('GET', 'dashboard_overview', []));
        $data = $response->data();

        self::assertSame(200, $response->statusCode());
        self::assertSame(3, $data['metadata']['assessment_id']);
        self::assertArrayHasKey('summary', $data);
        self::assertArrayHasKey('assessments', $data);
        self::assertArrayHasKey('subjects', $data);
        self::assertArrayHasKey('standards', $data);
        self::assertArrayHasKey('strands', $data);
        self::assertArrayHasKey('benchmarks', $data);
        self::assertArrayHasKey('warnings', $data);
        self::assertArrayHasKey('generation_time', $data);
    }

    public function testReturnsAPlainMessageWhenNoAssessmentExistsYet(): void
    {
        $action = new DashboardOverviewAction($this->makeServiceWithNoAssessment(), new DashboardResponseSerializer());

        $response = $action(new Request('GET', 'dashboard_overview', []));

        self::assertSame(200, $response->statusCode());
        self::assertArrayHasKey('message', $response->data());
    }
}
