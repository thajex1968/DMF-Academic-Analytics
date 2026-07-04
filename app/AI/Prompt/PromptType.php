<?php

declare(strict_types=1);

namespace DMF\AI\Prompt;

/**
 * Which shape a Prompt asks the provider to produce — lets an
 * AIProviderInterface (and its response parser, in InsightEngine /
 * RecommendationEngine) know whether to expect an AIInsight-shaped or
 * AIRecommendation-shaped answer, without inspecting the prompt text
 * itself.
 */
enum PromptType: string
{
    case INSIGHT = 'insight';
    case RECOMMENDATION = 'recommendation';
}
