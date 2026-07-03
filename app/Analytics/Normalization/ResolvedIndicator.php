<?php

declare(strict_types=1);

namespace DMF\Analytics\Normalization;

/**
 * One resolved `learning_indicators` row (ตัวชี้วัด) — the bottom of the
 * curriculum hierarchy, with its parent standard/strand already resolved.
 */
final class ResolvedIndicator
{
    public function __construct(
        public readonly int $id,
        public readonly string $indicatorCode,
        public readonly string $indicatorNameTh,
        public readonly int $gradeLevel,
        public readonly string $curriculumRevision,
        public readonly ResolvedStandard $standard,
    ) {
    }
}
