<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DateTimeImmutable;
use DMF\Analytics\Canonical\BenchmarkScope;
use DMF\Analytics\Dashboard\DashboardAlert;
use DMF\Analytics\Dashboard\DashboardAlertLevel;
use DMF\Analytics\Dashboard\DashboardAssessment;
use DMF\Analytics\Dashboard\DashboardBenchmark;
use DMF\Analytics\Dashboard\DashboardCard;
use DMF\Analytics\Dashboard\DashboardDataset;
use DMF\Analytics\Dashboard\DashboardHealth;
use DMF\Analytics\Dashboard\DashboardMetadata;
use DMF\Analytics\Dashboard\DashboardResponse;
use DMF\Analytics\Dashboard\DashboardResponseSerializer;
use DMF\Analytics\Dashboard\DashboardStandard;
use DMF\Analytics\Dashboard\DashboardStrand;
use DMF\Analytics\Dashboard\DashboardSubject;
use DMF\Analytics\Dashboard\DashboardSummary;
use PHPUnit\Framework\TestCase;

final class DashboardResponseSerializerTest extends TestCase
{
    private DashboardResponseSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new DashboardResponseSerializer();
    }

    public function testMetadataUsesSnakeCaseFieldsAndAtomDates(): void
    {
        $generatedAt = new DateTimeImmutable('2026-07-04T09:00:00+07:00');

        $array = $this->serializer->metadata(new DashboardMetadata(3, 'MATH', 2569, 6, $generatedAt));

        self::assertSame(3, $array['assessment_id']);
        self::assertSame('MATH', $array['subject_code']);
        self::assertSame(2569, $array['academic_year']);
        self::assertSame(6, $array['grade_level']);
        self::assertSame($generatedAt->format(DATE_ATOM), $array['generated_at']);
    }

    public function testBenchmarkSerializesTheScopeEnumToItsStringValue(): void
    {
        $array = $this->serializer->benchmark(new DashboardBenchmark(BenchmarkScope::REGION, 'MATH', 0.8, 0.7, 0.1));

        self::assertSame('region', $array['scope']);
    }

    public function testAlertSerializesTheLevelEnumToItsStringValue(): void
    {
        $array = $this->serializer->alert(new DashboardAlert(DashboardAlertLevel::CRITICAL, 'x', 'y'));

        self::assertSame('critical', $array['level']);
    }

    public function testResponseSerializesEveryCollectionAndNestedShape(): void
    {
        $response = new DashboardResponse(
            new DashboardMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new DashboardSummary(0.75, 30, 120, [new DashboardCard('Total Students', 30.0, null)], [
                new DashboardDataset('Subject Percent Correct', ['MATH' => 0.75]),
            ]),
            [new DashboardAssessment(3, 30, 120, 90, 0.75)],
            [new DashboardSubject('MATH', 0.75, null, null, null, null)],
            [new DashboardStandard(100, 'STD-A1', 0.6, null, null, null, null, null)],
            [new DashboardStrand(10, 'ST-A', 'MATH', 0.75, 20, 80, 60)],
            [new DashboardBenchmark(BenchmarkScope::SCHOOL, 'MATH', 0.75, 0.7, 0.05)],
            [new DashboardAlert(DashboardAlertLevel::WARNING, 'x', 'y')],
            new DateTimeImmutable(),
        );

        $array = $this->serializer->response($response);

        self::assertSame(3, $array['metadata']['assessment_id']);
        self::assertSame(30, $array['summary']['total_students']);
        self::assertCount(1, $array['summary']['cards']);
        self::assertCount(1, $array['summary']['datasets']);
        self::assertCount(1, $array['assessments']);
        self::assertCount(1, $array['subjects']);
        self::assertCount(1, $array['standards']);
        self::assertCount(1, $array['strands']);
        self::assertCount(1, $array['benchmarks']);
        self::assertCount(1, $array['warnings']);
        self::assertArrayHasKey('generation_time', $array);
    }

    public function testHealthSerializesLatestAssessmentAsANestedObjectWhenPresent(): void
    {
        $array = $this->serializer->health(new DashboardHealth('ok', 'ok', 3, 'MATH', 2569, null, 120, 5, []));

        self::assertSame(3, $array['latest_assessment']['assessment_id']);
        self::assertSame('MATH', $array['latest_assessment']['subject_code']);
        self::assertNull($array['latest_calculation']);
    }

    public function testHealthSerializesLatestAssessmentAsNullWhenNoneExists(): void
    {
        $array = $this->serializer->health(new DashboardHealth('ok', 'ok', null, null, null, null, 0, 0, []));

        self::assertNull($array['latest_assessment']);
    }
}
