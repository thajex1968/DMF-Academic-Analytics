<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Dashboard;

use DateTimeImmutable;
use DMF\Analytics\Dashboard\DashboardMetadata;
use PHPUnit\Framework\TestCase;

final class DashboardMetadataTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $generatedAt = new DateTimeImmutable('2026-07-04T09:00:00+07:00');

        $metadata = new DashboardMetadata(3, 'MATH', 2569, 6, $generatedAt);

        self::assertSame(3, $metadata->assessmentId);
        self::assertSame('MATH', $metadata->subjectCode);
        self::assertSame(2569, $metadata->academicYear);
        self::assertSame(6, $metadata->gradeLevel);
        self::assertSame($generatedAt, $metadata->generatedAt);
    }
}
