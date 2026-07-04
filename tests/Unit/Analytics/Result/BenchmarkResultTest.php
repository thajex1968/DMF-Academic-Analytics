<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Result;

use DMF\Analytics\Canonical\BenchmarkScope;
use DMF\Analytics\Result\BenchmarkResult;
use PHPUnit\Framework\TestCase;

final class BenchmarkResultTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $result = new BenchmarkResult(BenchmarkScope::PROVINCE, 'MATH', 0.80, 0.72, 0.08);

        self::assertSame(BenchmarkScope::PROVINCE, $result->scope);
        self::assertSame('MATH', $result->subjectCode);
        self::assertSame(0.80, $result->schoolPercentCorrect);
        self::assertSame(0.72, $result->benchmarkPercentCorrect);
        self::assertSame(0.08, $result->difference);
    }
}
