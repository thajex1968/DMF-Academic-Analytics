<?php

declare(strict_types=1);

namespace DMF\Analytics\Normalization;

/** One imported row ItemIndicatorNormalizer could not normalize, and why — always traceable to a row. */
final class UnresolvedMapping
{
    public function __construct(
        public readonly int $rowNumber,
        public readonly int $questionId,
        public readonly string $reason,
    ) {
    }
}
