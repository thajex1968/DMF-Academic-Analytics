<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Contracts;

use DateTimeImmutable;
use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Canonical\AssessmentAnalyticsRecord;
use DMF\Analytics\Contracts\CalculatorExecutionContext;
use PHPUnit\Framework\TestCase;

final class CalculatorExecutionContextTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 0, 0, 0),
            [],
            [],
            [],
            [],
        );
        $executedAt = new DateTimeImmutable('2026-07-04T09:00:00+07:00');

        $executionContext = new CalculatorExecutionContext($context, $executedAt);

        self::assertSame($context, $executionContext->context);
        self::assertSame($executedAt, $executionContext->executedAt);
    }
}
