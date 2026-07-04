<?php

declare(strict_types=1);

namespace DMF\AI\DTO;

/**
 * The structured result of one RecommendationEngine run. Pure data — never
 * computed from an average, a benchmark comparison, or any other Analytics
 * figure by this codebase (app/AI/Recommendation/RecommendationEngine.php
 * never calculates one); `priority`/`category`/`recommendation`/`reason`
 * are whatever the AIProviderInterface's response contained, parsed as-is.
 */
final class AIRecommendation
{
    public function __construct(
        public readonly AIRecommendationPriority $priority,
        public readonly string $category,
        public readonly string $recommendation,
        public readonly string $reason,
        public readonly float $confidence,
    ) {
    }
}
