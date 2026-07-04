<?php

declare(strict_types=1);

namespace DMF\AI\Contracts;

use DMF\AI\DTO\AIContext;
use DMF\AI\Prompt\Prompt;
use DMF\AI\Prompt\PromptType;
use DMF\Analytics\Dashboard\DashboardAssessment;
use DMF\Analytics\Dashboard\DashboardHealth;
use DMF\Analytics\Dashboard\DashboardSummary;

/**
 * Builds a Prompt from Analytics DTOs only — never a repository, entity,
 * SQL, PDO handle, database row, or raw array (this Sprint's explicit
 * Prompt Rules). Typed directly against the Dashboard DTOs
 * (`DMF\Analytics\Dashboard\*`, Sprint 4 Phase 3/Sprint 5) rather than a
 * generic "bag of analytics data," since exactly one concrete builder
 * (DashboardPromptBuilder) exists this phase — a looser signature would be
 * speculative (YAGNI) until a second, differently-shaped builder is
 * actually needed.
 */
interface PromptBuilderInterface
{
    public function build(
        PromptType $type,
        AIContext $context,
        DashboardSummary $summary,
        DashboardHealth $health,
        DashboardAssessment $assessment,
    ): Prompt;
}
