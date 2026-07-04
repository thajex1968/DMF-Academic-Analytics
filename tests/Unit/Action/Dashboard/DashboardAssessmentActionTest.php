<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Action\Dashboard;

use DMF\Action\Dashboard\DashboardAssessmentAction;
use DMF\Analytics\Dashboard\DashboardResponseSerializer;
use Dmf\Core\Http\Request;

final class DashboardAssessmentActionTest extends DashboardActionTestCase
{
    public function testReturnsMetadataAndAssessmentsOnly(): void
    {
        $action = new DashboardAssessmentAction($this->makeServiceWithAssessment(), new DashboardResponseSerializer());

        $response = $action(new Request('GET', 'dashboard_assessment', []));
        $data = $response->data();

        self::assertSame(200, $response->statusCode());
        self::assertSame(3, $data['metadata']['assessment_id']);
        self::assertCount(1, $data['assessments']);
        self::assertSame(0.75, $data['assessments'][0]['percent_correct']);
        self::assertArrayNotHasKey('subjects', $data);
        self::assertArrayNotHasKey('benchmarks', $data);
    }

    public function testReturnsAPlainMessageWhenNoAssessmentExistsYet(): void
    {
        $action = new DashboardAssessmentAction(
            $this->makeServiceWithNoAssessment(),
            new DashboardResponseSerializer(),
        );

        $response = $action(new Request('GET', 'dashboard_assessment', []));

        self::assertArrayHasKey('message', $response->data());
    }
}
