<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Result;

use DMF\Analytics\Result\SubjectResult;
use PHPUnit\Framework\TestCase;

final class SubjectResultTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $distribution = ['0-59' => 2, '60-79' => 5, '80-100' => 3];

        $result = new SubjectResult('MATH', 0.65, 62.5, 95.0, 30.0, $distribution);

        self::assertSame('MATH', $result->subjectCode);
        self::assertSame(0.65, $result->percentCorrect);
        self::assertSame(62.5, $result->average);
        self::assertSame(95.0, $result->highest);
        self::assertSame(30.0, $result->lowest);
        self::assertSame($distribution, $result->distribution);
    }

    public function testEveryStatisticFieldAcceptsNullForUnavailableData(): void
    {
        $result = new SubjectResult('MATH', null, null, null, null, null);

        self::assertNull($result->percentCorrect);
        self::assertNull($result->average);
        self::assertNull($result->highest);
        self::assertNull($result->lowest);
        self::assertNull($result->distribution);
    }
}
