<?php

declare(strict_types=1);

namespace DMF\AI\Contracts;

use DMF\AI\DTO\AIContext;
use DMF\AI\DTO\AIRecommendation;
use DMF\Analytics\Dashboard\DashboardAssessment;
use DMF\Analytics\Dashboard\DashboardHealth;
use DMF\Analytics\Dashboard\DashboardSummary;

/**
 * Produces an AIRecommendation from Analytics DTOs — recommendations only;
 * an implementation must never calculate an average, calculate a
 * benchmark, or infer a hidden value itself.
 */
interface RecommendationGeneratorInterface
{
    public function generateRecommendation(
        AIContext $context,
        DashboardSummary $summary,
        DashboardHealth $health,
        DashboardAssessment $assessment,
    ): AIRecommendation;
}
