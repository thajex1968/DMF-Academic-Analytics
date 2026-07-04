<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DMF\Analytics\Canonical\BenchmarkScope;
use DMF\Analytics\Dashboard\DashboardBenchmark;
use PHPUnit\Framework\TestCase;

final class DashboardBenchmarkTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $benchmark = new DashboardBenchmark(BenchmarkScope::PROVINCE, 'MATH', 0.8, 0.72, 0.08);

        self::assertSame(BenchmarkScope::PROVINCE, $benchmark->scope);
        self::assertSame('MATH', $benchmark->subjectCode);
        self::assertSame(0.8, $benchmark->schoolPercentCorrect);
        self::assertSame(0.72, $benchmark->benchmarkPercentCorrect);
        self::assertSame(0.08, $benchmark->difference);
    }
}
