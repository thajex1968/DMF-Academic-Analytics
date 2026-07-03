<?php

declare(strict_types=1);

namespace DMF\Analytics\Normalization;

/**
 * The outcome of one ItemIndicatorNormalizer::normalize() run. Pure DTO — no
 * behavior, nothing computed here that wasn't already computed by the
 * normalizer. Unlike ScoreImportService's ImportResult (FR-006's "no partial
 * commits"), this is not all-or-nothing: $records and $unresolvedMappings
 * coexist by design, since deciding whether a partially-normalized batch may
 * proceed to Storage is a future caller's policy decision, not this layer's.
 */
final class NormalizationResult
{
    /**
     * @param NormalizedRecord[] $records
     * @param UnresolvedMapping[] $unresolvedMappings
     * @param string[] $warnings
     */
    public function __construct(
        public readonly array $records,
        public readonly array $unresolvedMappings,
        public readonly array $warnings,
        public readonly int $totalRows,
        public readonly int $normalizedCount,
        public readonly int $unresolvedCount,
    ) {
    }

    /**
     * @param NormalizedRecord[] $records
     * @param UnresolvedMapping[] $unresolvedMappings
     * @param string[] $warnings
     */
    public static function build(array $records, array $unresolvedMappings, array $warnings, int $totalRows): self
    {
        return new self(
            $records,
            $unresolvedMappings,
            $warnings,
            $totalRows,
            count($records),
            count($unresolvedMappings),
        );
    }
}
