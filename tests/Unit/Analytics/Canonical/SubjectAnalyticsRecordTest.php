<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Canonical;

use DMF\Analytics\Canonical\SubjectAnalyticsRecord;
use PHPUnit\Framework\TestCase;

final class SubjectAnalyticsRecordTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $record = new SubjectAnalyticsRecord('MATH', 30, 120, 96);

        self::assertSame('MATH', $record->subjectCode);
        self::assertSame(30, $record->studentCount);
        self::assertSame(120, $record->responseCount);
        self::assertSame(96, $record->correctCount);
    }
}
