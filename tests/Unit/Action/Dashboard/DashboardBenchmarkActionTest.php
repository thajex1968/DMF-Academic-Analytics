<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Action\Dashboard;

use DMF\Action\Dashboard\DashboardBenchmarkAction;
use DMF\Analytics\Dashboard\DashboardResponseSerializer;
use Dmf\Core\Http\Request;

final class DashboardBenchmarkActionTest extends DashboardActionTestCase
{
    public function testReturnsMetadataAndAnEmptyBenchmarkListSinceNoAdapterPopulatesItYet(): void
    {
        $action = new DashboardBenchmarkAction($this->makeServiceWithAssessment(), new DashboardResponseSerializer());

        $response = $action(new Request('GET', 'dashboard_benchmark', []));
        $data = $response->data();

        self::assertSame(200, $response->statusCode());
        self::assertSame(3, $data['metadata']['assessment_id']);
        self::assertSame([], $data['benchmarks']);
        self::assertArrayNotHasKey('subjects', $data);
    }

    public function testReturnsAPlainMessageWhenNoAssessmentExistsYet(): void
    {
        $action = new DashboardBenchmarkAction($this->makeServiceWithNoAssessment(), new DashboardResponseSerializer());

        $response = $action(new Request('GET', 'dashboard_benchmark', []));

        self::assertArrayHasKey('message', $response->data());
    }
}
