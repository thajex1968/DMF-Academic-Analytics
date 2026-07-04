<?php

declare(strict_types=1);

namespace DMF\Analytics\Canonical;

use DateTimeImmutable;

/**
 * Source-agnostic identity for one Analytics run: which assessment,
 * subject, grade, and academic year an AnalyticsContext covers. Never
 * carries an assessment type, source name, or provider — only the
 * Assessment Adapter Layer knows those (docs/02-System-Architecture.md
 * §8.1); the Analytics Engine reasons only in terms of this metadata and
 * the Canonical records alongside it.
 */
final class AnalyticsMetadata
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
