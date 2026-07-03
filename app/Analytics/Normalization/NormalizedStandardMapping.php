<?php

declare(strict_types=1);

namespace DMF\Analytics\Normalization;

/**
 * One question's full, normalized standard mapping: exactly one primary
 * indicator, plus zero or more distinct secondary indicators (PRD FR-009).
 * Each indicator carries its own resolved standard/strand chain — a
 * question's secondary indicators are not assumed to share the primary
 * indicator's standard or strand.
 */
final class NormalizedStandardMapping
{
    /** @param ResolvedIndicator[] $secondaryIndicators */
    public function __construct(
        public readonly int $questionId,
        public readonly ResolvedIndicator $primaryIndicator,
        public readonly array $secondaryIndicators,
    ) {
    }
}
