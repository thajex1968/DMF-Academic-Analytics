<?php

declare(strict_types=1);

namespace DMF\AI\Contracts;

use DMF\AI\DTO\AIContext;
use DMF\AI\DTO\AIInsight;
use DMF\Analytics\Dashboard\DashboardAssessment;
use DMF\Analytics\Dashboard\DashboardHealth;
use DMF\Analytics\Dashboard\DashboardSummary;

/** Produces an AIInsight from Analytics DTOs — no business calculation, no analytics, no SQL. */
interface InsightGeneratorInterface
{
    public function generateInsight(
        AIContext $context,
        DashboardSummary $summary,
        DashboardHealth $health,
        DashboardAssessment $assessment,
    ): AIInsight;
}
