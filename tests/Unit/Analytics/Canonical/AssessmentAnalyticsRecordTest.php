<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Canonical;

use DMF\Analytics\Canonical\AssessmentAnalyticsRecord;
use PHPUnit\Framework\TestCase;

final class AssessmentAnalyticsRecordTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $record = new AssessmentAnalyticsRecord(3, 30, 120, 96);

        self::assertSame(3, $record->assessmentId);
        self::assertSame(30, $record->studentCount);
        self::assertSame(120, $record->responseCount);
        self::assertSame(96, $record->correctCount);
    }
}
