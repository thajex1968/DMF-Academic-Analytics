<?php

declare(strict_types=1);

namespace DMF\Import\Template;

/**
 * Resolves which ImportTemplate applies to a queued import job — the gap
 * ScoreImportService's own docblock names ("the caller resolves *which*
 * template applies... this service does not guess a template key from
 * assessment metadata, since no documented convention establishes one").
 *
 * v1.0 (decisions/IDR-009): always returns the one template registered
 * under `$defaultTemplateKey`, regardless of `$assessmentId` — no real สทศ
 * file specification has been provided yet, the same gap T2.2/T2.3 already
 * flagged and stopped at rather than fabricate. `$assessmentId` is accepted
 * now so a future, real per-academic-year lookup replaces only this
 * method's body — every caller (DMF\Import\Cron\ImportJobRunner) stays
 * unchanged.
 */
final class TemplateResolver
{
    public function __construct(
        private readonly TemplateRegistry $registry,
        private readonly string $defaultTemplateKey,
    ) {
    }

    public function resolveForAssessment(int $assessmentId): ImportTemplate
    {
        return $this->registry->get($this->defaultTemplateKey);
    }
}
