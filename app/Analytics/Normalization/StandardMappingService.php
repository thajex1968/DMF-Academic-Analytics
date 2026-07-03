<?php

declare(strict_types=1);

namespace DMF\Analytics\Normalization;

/**
 * Composes QuestionStandardResolver's per-indicator resolution into one
 * question's full NormalizedStandardMapping — one primary indicator, zero or
 * more distinct secondary indicators. No analytics; this only shapes
 * already-resolved data.
 */
final class StandardMappingService
{
    public function __construct(
        private readonly QuestionStandardResolver $resolver,
    ) {
    }

    /** @throws UnresolvedMappingException */
    public function map(int $questionId): NormalizedStandardMapping
    {
        $primary = $this->resolver->resolvePrimaryIndicator($questionId);
        $secondary = $this->resolver->resolveSecondaryIndicators($questionId);

        // Duplicate indicator protection: a secondary link pointing at the same
        // indicator as the primary (or, defensively, repeated within the secondary
        // set itself, though the table's own unique key already rules that out) must
        // never be double-counted in the normalized mapping.
        $uniqueSecondary = [];

        foreach ($secondary as $indicator) {
            if ($indicator->id === $primary->id) {
                continue;
            }

            $uniqueSecondary[$indicator->id] = $indicator;
        }

        return new NormalizedStandardMapping($questionId, $primary, array_values($uniqueSecondary));
    }
}
