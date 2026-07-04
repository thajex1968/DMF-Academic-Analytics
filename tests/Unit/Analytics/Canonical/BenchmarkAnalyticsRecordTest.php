<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Canonical;

use DMF\Analytics\Canonical\BenchmarkAnalyticsRecord;
use DMF\Analytics\Canonical\BenchmarkScope;
use PHPUnit\Framework\TestCase;

final class BenchmarkAnalyticsRecordTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $record = new BenchmarkAnalyticsRecord(BenchmarkScope::REGION, 'MATH', 0.68);

        self::assertSame(BenchmarkScope::REGION, $record->scope);
        self::assertSame('MATH', $record->subjectCode);
        self::assertSame(0.68, $record->comparisonValue);
    }
}
