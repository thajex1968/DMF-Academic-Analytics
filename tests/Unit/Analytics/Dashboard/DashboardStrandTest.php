<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DMF\Analytics\Dashboard\DashboardStrand;
use PHPUnit\Framework\TestCase;

final class DashboardStrandTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $strand = new DashboardStrand(10, 'ST-A', 'MATH', 0.75, 20, 80, 60);

        self::assertSame(10, $strand->strandId);
        self::assertSame('ST-A', $strand->strandCode);
        self::assertSame('MATH', $strand->subjectCode);
        self::assertSame(0.75, $strand->percentCorrect);
        self::assertSame(20, $strand->studentCount);
        self::assertSame(80, $strand->responseCount);
        self::assertSame(60, $strand->correctCount);
    }

    public function testPercentCorrectAcceptsNull(): void
    {
        $strand = new DashboardStrand(10, 'ST-A', 'MATH', null, 0, 0, 0);

        self::assertNull($strand->percentCorrect);
    }
}
