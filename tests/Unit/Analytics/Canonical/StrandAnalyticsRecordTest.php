<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Canonical;

use DMF\Analytics\Canonical\StrandAnalyticsRecord;
use PHPUnit\Framework\TestCase;

final class StrandAnalyticsRecordTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $record = new StrandAnalyticsRecord(10, 'ค1', 'MATH', 20, 80, 60);

        self::assertSame(10, $record->strandId);
        self::assertSame('ค1', $record->strandCode);
        self::assertSame('MATH', $record->subjectCode);
        self::assertSame(20, $record->studentCount);
        self::assertSame(80, $record->responseCount);
        self::assertSame(60, $record->correctCount);
    }
}
