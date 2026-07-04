<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Canonical;

use DMF\Analytics\Canonical\BenchmarkScope;
use PHPUnit\Framework\TestCase;

final class BenchmarkScopeTest extends TestCase
{
    public function testEveryCaseHasItsExpectedStringValue(): void
    {
        self::assertSame('school', BenchmarkScope::SCHOOL->value);
        self::assertSame('province', BenchmarkScope::PROVINCE->value);
        self::assertSame('region', BenchmarkScope::REGION->value);
        self::assertSame('country', BenchmarkScope::COUNTRY->value);
    }
}
