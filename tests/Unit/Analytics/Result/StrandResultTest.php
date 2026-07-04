<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Result;

use DMF\Analytics\Result\StrandResult;
use PHPUnit\Framework\TestCase;

final class StrandResultTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $result = new StrandResult(10, 'ST-A', 'MATH', 0.75, 20, 80, 60);

        self::assertSame(10, $result->strandId);
        self::assertSame('ST-A', $result->strandCode);
        self::assertSame('MATH', $result->subjectCode);
        self::assertSame(0.75, $result->percentCorrect);
        self::assertSame(20, $result->studentCount);
        self::assertSame(80, $result->responseCount);
        self::assertSame(60, $result->correctCount);
    }

    public function testPercentCorrectAcceptsNullForUnavailableData(): void
    {
        $result = new StrandResult(10, 'ST-A', 'MATH', null, 0, 0, 0);

        self::assertNull($result->percentCorrect);
    }
}
