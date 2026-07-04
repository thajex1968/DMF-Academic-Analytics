<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DateTimeImmutable;
use DMF\Analytics\Dashboard\DashboardAlert;
use DMF\Analytics\Dashboard\DashboardAlertLevel;
use DMF\Analytics\Dashboard\DashboardHealth;
use PHPUnit\Framework\TestCase;

final class DashboardHealthTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $warnings = [new DashboardAlert(DashboardAlertLevel::INFO, 'x', 'y')];

        $health = new DashboardHealth('ok', 'ok', 3, 'MATH', 2569, null, 120, 5, $warnings);

        self::assertSame('ok', $health->importStatus);
        self::assertSame('ok', $health->analyticsStatus);
        self::assertSame(3, $health->latestAssessmentId);
        self::assertSame('MATH', $health->latestAssessmentSubjectCode);
        self::assertSame(2569, $health->latestAssessmentAcademicYear);
        self::assertNull($health->latestCalculation);
        self::assertSame(120, $health->totalStudents);
        self::assertSame(5, $health->totalAssessments);
        self::assertSame($warnings, $health->warnings);
    }

    public function testLatestAssessmentFieldsAndLatestCalculationAcceptNull(): void
    {
        $health = new DashboardHealth('ok', 'ok', null, null, null, null, 0, 0, []);

        self::assertNull($health->latestAssessmentId);
        self::assertNull($health->latestAssessmentSubjectCode);
        self::assertNull($health->latestAssessmentAcademicYear);
        self::assertNull($health->latestCalculation);
    }

    public function testLatestCalculationAcceptsATimestampWhenPresent(): void
    {
        $timestamp = new DateTimeImmutable('2026-07-04T09:00:00+07:00');

        $health = new DashboardHealth('ok', 'ok', null, null, null, $timestamp, 0, 0, []);

        self::assertSame($timestamp, $health->latestCalculation);
    }
}
