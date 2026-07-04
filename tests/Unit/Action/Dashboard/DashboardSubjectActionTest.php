<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Action\Dashboard;

use DMF\Action\Dashboard\DashboardSubjectAction;
use DMF\Analytics\Dashboard\DashboardResponseSerializer;
use Dmf\Core\Http\Request;

final class DashboardSubjectActionTest extends DashboardActionTestCase
{
    public function testReturnsMetadataSubjectsStrandsAndStandards(): void
    {
        $action = new DashboardSubjectAction($this->makeServiceWithAssessment(), new DashboardResponseSerializer());

        $response = $action(new Request('GET', 'dashboard_subjects', []));
        $data = $response->data();

        self::assertSame(200, $response->statusCode());
        self::assertCount(1, $data['subjects']);
        self::assertSame('MATH', $data['subjects'][0]['subject_code']);
        self::assertCount(1, $data['strands']);
        self::assertCount(1, $data['standards']);
        self::assertArrayNotHasKey('benchmarks', $data);
    }

    public function testReturnsAPlainMessageWhenNoAssessmentExistsYet(): void
    {
        $action = new DashboardSubjectAction($this->makeServiceWithNoAssessment(), new DashboardResponseSerializer());

        $response = $action(new Request('GET', 'dashboard_subjects', []));

        self::assertArrayHasKey('message', $response->data());
    }
}
