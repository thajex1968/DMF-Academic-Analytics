<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

use DateTimeImmutable;

/**
 * Which assessment a DashboardResponse describes — carried straight from
 * Canonical\AnalyticsMetadata. Never an assessment type, source name, or
 * provider (Source Independence, docs/02-System-Architecture.md §8.1).
 */
final class DashboardMetadata
{
    public function __construct(
        public readonly int $assessmentId,
        public readonly string $subjectCode,
        public readonly int $academicYear,
        public readonly int $gradeLevel,
        public readonly DateTimeImmutable $generatedAt,
    ) {
    }
}
