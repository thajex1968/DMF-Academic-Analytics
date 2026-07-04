<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Aggregation;

use DateTimeImmutable;
use DMF\Analytics\Aggregation\AssessmentSummaryAggregator;
use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Canonical\AssessmentAnalyticsRecord;
use PHPUnit\Framework\TestCase;

final class AssessmentSummaryAggregatorTest extends TestCase
{
    public function testComputesPercentCorrectFromThePooledCounts(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 30, 120, 90),
            [],
            [],
            [],
            [],
        );

        $result = (new AssessmentSummaryAggregator())->aggregate($context);

        self::assertSame(3, $result->assessmentId);
        self::assertSame(30, $result->studentCount);
        self::assertSame(120, $result->responseCount);
        self::assertSame(90, $result->correctCount);
        self::assertSame(0.75, $result->percentCorrect);
    }

    public function testPercentCorrectIsNullWhenThereAreNoResponses(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 0, 0, 0),
            [],
            [],
            [],
            [],
        );

        $result = (new AssessmentSummaryAggregator())->aggregate($context);

        self::assertNull($result->percentCorrect);
    }
}
