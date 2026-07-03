<?php

declare(strict_types=1);

namespace DMF\Analytics\Normalization;

/** One resolved `learning_strands` row (สาระการเรียนรู้) — the top of the curriculum hierarchy. */
final class ResolvedStrand
{
    public function __construct(
        public readonly int $id,
        public readonly string $subjectCode,
        public readonly string $strandCode,
        public readonly string $strandNameTh,
    ) {
    }
}
