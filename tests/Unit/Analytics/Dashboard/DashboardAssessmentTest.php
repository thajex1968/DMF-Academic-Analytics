<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DMF\Analytics\Dashboard\DashboardAssessment;
use PHPUnit\Framework\TestCase;

final class DashboardAssessmentTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $assessment = new DashboardAssessment(3, 30, 120, 96, 0.8);

        self::assertSame(3, $assessment->assessmentId);
        self::assertSame(30, $assessment->studentCount);
        self::assertSame(120, $assessment->responseCount);
        self::assertSame(96, $assessment->correctCount);
        self::assertSame(0.8, $assessment->percentCorrect);
    }

    public function testPercentCorrectAcceptsNull(): void
    {
        $assessment = new DashboardAssessment(3, 0, 0, 0, null);

        self::assertNull($assessment->percentCorrect);
    }
}
