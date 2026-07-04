<?php

declare(strict_types=1);

namespace DMF\Analytics\Canonical;

/**
 * The one object every Analytics Calculator receives (RFC-004,
 * docs/02-System-Architecture.md §8.1). Assembled by
 * AnalyticsContextFactory from Normalization's output — nothing in this
 * class, or anything it references, ever names an assessment type, source,
 * or provider.
 *
 * `$benchmarkRecords` (Sprint 4 Phase 2) defaults to an empty array so every
 * Phase 1 call site is unaffected — AnalyticsContextFactory has no source of
 * benchmark data today (Normalization/Level 2 evidence never carries a
 * published comparison figure); only a future Level 1 Assessment Adapter
 * would ever populate it.
 */
final class AnalyticsContext
{
    /**
     * @param SubjectAnalyticsRecord[] $subjectRecords
     * @param StrandAnalyticsRecord[] $strandRecords
     * @param StandardAnalyticsRecord[] $standardRecords
     * @param QuestionAnalyticsRecord[] $questionRecords
     * @param BenchmarkAnalyticsRecord[] $benchmarkRecords
     */
    public function __construct(
        public readonly AnalyticsMetadata $metadata,
        public readonly AssessmentAnalyticsRecord $assessmentRecord,
        public readonly array $subjectRecords,
        public readonly array $strandRecords,
        public readonly array $standardRecords,
        public readonly array $questionRecords,
        public readonly array $benchmarkRecords = [],
    ) {
    }
}
