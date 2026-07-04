<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DateTimeImmutable;
use DMF\Analytics\Canonical\BenchmarkScope;
use DMF\Analytics\Dashboard\DashboardAlert;
use DMF\Analytics\Dashboard\DashboardAlertLevel;
use DMF\Analytics\Dashboard\DashboardAssessment;
use DMF\Analytics\Dashboard\DashboardBenchmark;
use DMF\Analytics\Dashboard\DashboardMetadata;
use DMF\Analytics\Dashboard\DashboardResponse;
use DMF\Analytics\Dashboard\DashboardStandard;
use DMF\Analytics\Dashboard\DashboardStrand;
use DMF\Analytics\Dashboard\DashboardSubject;
use DMF\Analytics\Dashboard\DashboardSummary;
use PHPUnit\Framework\TestCase;

final class DashboardResponseTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $metadata = new DashboardMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable());
        $summary = new DashboardSummary(0.75, 30, 120, [], []);
        $assessments = [new DashboardAssessment(3, 30, 120, 90, 0.75)];
        $subjects = [new DashboardSubject('MATH', 0.75, null, null, null, null)];
        $standards = [new DashboardStandard(100, 'STD-A1', 0.6, null, null, null, null, null)];
        $strands = [new DashboardStrand(10, 'ST-A', 'MATH', 0.75, 20, 80, 60)];
        $benchmarks = [new DashboardBenchmark(BenchmarkScope::SCHOOL, 'MATH', 0.75, 0.7, 0.05)];
        $warnings = [new DashboardAlert(DashboardAlertLevel::WARNING, 'x', 'y')];
        $generationTime = new DateTimeImmutable();

        $response = new DashboardResponse(
            $metadata,
            $summary,
            $assessments,
            $subjects,
            $standards,
            $strands,
            $benchmarks,
            $warnings,
            $generationTime,
        );

        self::assertSame($metadata, $response->metadata);
        self::assertSame($summary, $response->summary);
        self::assertSame($assessments, $response->assessments);
        self::assertSame($subjects, $response->subjects);
        self::assertSame($standards, $response->standards);
        self::assertSame($strands, $response->strands);
        self::assertSame($benchmarks, $response->benchmarks);
        self::assertSame($warnings, $response->warnings);
        self::assertSame($generationTime, $response->generationTime);
    }
}
