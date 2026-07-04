<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Canonical;

use DateTimeImmutable;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use PHPUnit\Framework\TestCase;

final class AnalyticsMetadataTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $generatedAt = new DateTimeImmutable('2026-07-04T09:00:00+07:00');

        $metadata = new AnalyticsMetadata(3, 'MATH', 2569, 6, $generatedAt);

        self::assertSame(3, $metadata->assessmentId);
        self::assertSame('MATH', $metadata->subjectCode);
        self::assertSame(2569, $metadata->academicYear);
        self::assertSame(6, $metadata->gradeLevel);
        self::assertSame($generatedAt, $metadata->generatedAt);
    }
}
