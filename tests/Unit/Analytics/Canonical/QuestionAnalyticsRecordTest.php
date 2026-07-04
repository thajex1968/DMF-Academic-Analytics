<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Canonical;

use DMF\Analytics\Canonical\QuestionAnalyticsRecord;
use PHPUnit\Framework\TestCase;

final class QuestionAnalyticsRecordTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $record = new QuestionAnalyticsRecord(1001, 100, 2, 3, 2);

        self::assertSame(1001, $record->questionId);
        self::assertSame(100, $record->standardId);
        self::assertSame(2, $record->studentCount);
        self::assertSame(3, $record->responseCount);
        self::assertSame(2, $record->correctCount);
    }
}
