<?php

declare(strict_types=1);

namespace DMF\Analytics\Normalization;

/**
 * One imported student response, normalized against its question's
 * standard mapping. Pure evidence — no mastery, no aggregation, no score of
 * any kind is computed or stored here.
 */
final class NormalizedRecord
{
    public function __construct(
        public readonly string $studentId,
        public readonly NormalizedStandardMapping $mapping,
        public readonly ?string $selectedChoice,
        public readonly bool $isCorrect,
    ) {
    }
}
