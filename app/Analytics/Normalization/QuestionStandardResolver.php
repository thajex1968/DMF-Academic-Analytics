<?php

declare(strict_types=1);

namespace DMF\Analytics\Normalization;

use DMF\Repository\LearningIndicatorRepository;
use DMF\Repository\LearningStandardRepository;
use DMF\Repository\LearningStrandRepository;
use DMF\Repository\QuestionRepository;
use DMF\Repository\QuestionSecondaryIndicatorRepository;

/**
 * Walks the question → indicator → standard → strand chain
 * (docs/Domain-Model.md §5/§6) one FK hop at a time, purely by reading the
 * already-seeded catalogue — it never fabricates a link that isn't there
 * (PRD FR-009's "never invent missing mappings" rule). Any missing row at
 * any hop raises UnresolvedMappingException naming exactly which id could
 * not be found, rather than silently substituting a placeholder.
 */
final class QuestionStandardResolver
{
    public function __construct(
        private readonly QuestionRepository $questions,
        private readonly QuestionSecondaryIndicatorRepository $secondaryIndicators,
        private readonly LearningIndicatorRepository $indicators,
        private readonly LearningStandardRepository $standards,
        private readonly LearningStrandRepository $strands,
    ) {
    }

    /** @throws UnresolvedMappingException */
    public function resolvePrimaryIndicator(int $questionId): ResolvedIndicator
    {
        $question = $this->questions->findById($questionId);

        if ($question === null) {
            throw new UnresolvedMappingException(sprintf('Question %d not found.', $questionId));
        }

        return $this->resolveIndicator((int) $question['primary_indicator_id']);
    }

    /**
     * @return ResolvedIndicator[] Every secondary indicator linked to the question, in no
     *     particular order.
     * @throws UnresolvedMappingException If a linked secondary indicator itself cannot be
     *     resolved — the schema's own FK makes this practically impossible against a real
     *     database, but this resolver never assumes referential integrity holds; it verifies it.
     */
    public function resolveSecondaryIndicators(int $questionId): array
    {
        $links = $this->secondaryIndicators->findByQuestion($questionId);

        $resolved = [];

        foreach ($links as $link) {
            $resolved[] = $this->resolveIndicator((int) $link['indicator_id']);
        }

        return $resolved;
    }

    /** @throws UnresolvedMappingException */
    private function resolveIndicator(int $indicatorId): ResolvedIndicator
    {
        $indicator = $this->indicators->findById($indicatorId);

        if ($indicator === null) {
            throw new UnresolvedMappingException(sprintf('Learning indicator %d not found.', $indicatorId));
        }

        return new ResolvedIndicator(
            (int) $indicator['id'],
            (string) $indicator['indicator_code'],
            (string) $indicator['indicator_name_th'],
            (int) $indicator['grade_level'],
            (string) $indicator['curriculum_revision'],
            $this->resolveStandard((int) $indicator['standard_id']),
        );
    }

    /** @throws UnresolvedMappingException */
    private function resolveStandard(int $standardId): ResolvedStandard
    {
        $standard = $this->standards->findById($standardId);

        if ($standard === null) {
            throw new UnresolvedMappingException(sprintf('Learning standard %d not found.', $standardId));
        }

        return new ResolvedStandard(
            (int) $standard['id'],
            (string) $standard['standard_code'],
            (string) $standard['standard_name_th'],
            $this->resolveStrand((int) $standard['strand_id']),
        );
    }

    /** @throws UnresolvedMappingException */
    private function resolveStrand(int $strandId): ResolvedStrand
    {
        $strand = $this->strands->findById($strandId);

        if ($strand === null) {
            throw new UnresolvedMappingException(sprintf('Learning strand %d not found.', $strandId));
        }

        return new ResolvedStrand(
            (int) $strand['id'],
            (string) $strand['subject_code'],
            (string) $strand['strand_code'],
            (string) $strand['strand_name_th'],
        );
    }
}
