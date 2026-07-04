<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DMF\Analytics\Dashboard\DashboardSubject;
use PHPUnit\Framework\TestCase;

final class DashboardSubjectTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $distribution = ['0-59' => 2, '60-100' => 8];

        $subject = new DashboardSubject('MATH', 0.7, 65.0, 95.0, 30.0, $distribution);

        self::assertSame('MATH', $subject->subjectCode);
        self::assertSame(0.7, $subject->percentCorrect);
        self::assertSame(65.0, $subject->average);
        self::assertSame(95.0, $subject->highest);
        self::assertSame(30.0, $subject->lowest);
        self::assertSame($distribution, $subject->distribution);
    }

    public function testEveryStatisticFieldAcceptsNull(): void
    {
        $subject = new DashboardSubject('MATH', null, null, null, null, null);

        self::assertNull($subject->percentCorrect);
        self::assertNull($subject->average);
        self::assertNull($subject->highest);
        self::assertNull($subject->lowest);
        self::assertNull($subject->distribution);
    }
}
