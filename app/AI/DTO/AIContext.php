<?php

declare(strict_types=1);

namespace DMF\AI\DTO;

/**
 * Which assessment/school an AI Foundation call is for, plus locale
 * concerns (language/timezone) — never a source-specific field
 * (assessment type, provider, report format), matching the same
 * source-independence discipline Analytics already follows
 * (docs/02-System-Architecture.md §8.1).
 */
final class AIContext
{
    public function __construct(
        public readonly int $assessmentId,
        public readonly int $schoolId,
        public readonly string $language,
        public readonly string $timezone,
    ) {
    }
}
