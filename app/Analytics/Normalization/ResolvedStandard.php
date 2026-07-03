<?php

declare(strict_types=1);

namespace DMF\Analytics\Normalization;

/** One resolved `learning_standards` row (มาตรฐานการเรียนรู้), with its parent strand already resolved. */
final class ResolvedStandard
{
    public function __construct(
        public readonly int $id,
        public readonly string $standardCode,
        public readonly string $standardNameTh,
        public readonly ResolvedStrand $strand,
    ) {
    }
}
