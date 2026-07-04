<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Result;

use DMF\Analytics\Result\StandardResult;
use PHPUnit\Framework\TestCase;

final class StandardResultTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $result = new StandardResult(100, 'STD-A1', 0.6, 0.5, 0.55, 0.1, 0.9, 0.2);

        self::assertSame(100, $result->standardId);
        self::assertSame('STD-A1', $result->standardCode);
        self::assertSame(0.6, $result->percentCorrect);
        self::assertSame(0.5, $result->mean);
        self::assertSame(0.55, $result->median);
        self::assertSame(0.1, $result->min);
        self::assertSame(0.9, $result->max);
        self::assertSame(0.2, $result->standardDeviation);
    }

    public function testEveryStatisticFieldAcceptsNullForUnavailableData(): void
    {
        $result = new StandardResult(100, 'STD-A1', null, null, null, null, null, null);

        self::assertNull($result->percentCorrect);
        self::assertNull($result->mean);
        self::assertNull($result->median);
        self::assertNull($result->min);
        self::assertNull($result->max);
        self::assertNull($result->standardDeviation);
    }
}
