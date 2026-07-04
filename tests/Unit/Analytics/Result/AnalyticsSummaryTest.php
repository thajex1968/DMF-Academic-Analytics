<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Result;

use DateTimeImmutable;
use DMF\Analytics\Result\AnalyticsSummary;
use PHPUnit\Framework\TestCase;

final class AnalyticsSummaryTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $computedAt = new DateTimeImmutable('2026-07-04T09:00:00+07:00');

        $summary = new AnalyticsSummary('placeholder-calculator', 10, 1, 2, $computedAt);

        self::assertSame('placeholder-calculator', $summary->calculatorName);
        self::assertSame(10, $summary->recordCount);
        self::assertSame(1, $summary->issueCount);
        self::assertSame(2, $summary->warningCount);
        self::assertSame($computedAt, $summary->computedAt);
    }
}
