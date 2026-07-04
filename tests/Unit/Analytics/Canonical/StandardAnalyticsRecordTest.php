<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Canonical;

use DMF\Analytics\Canonical\StandardAnalyticsRecord;
use PHPUnit\Framework\TestCase;

final class StandardAnalyticsRecordTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $record = new StandardAnalyticsRecord(100, 'ค1.1', 10, 15, 40, 30);

        self::assertSame(100, $record->standardId);
        self::assertSame('ค1.1', $record->standardCode);
        self::assertSame(10, $record->strandId);
        self::assertSame(15, $record->studentCount);
        self::assertSame(40, $record->responseCount);
        self::assertSame(30, $record->correctCount);
    }
}
