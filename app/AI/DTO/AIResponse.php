<?php

declare(strict_types=1);

namespace DMF\AI\DTO;

/**
 * The raw outcome of one AIProviderInterface::generate() call — provider
 * identity, cost/latency accounting, and the provider's own raw response
 * text. `InsightEngine`/`RecommendationEngine` parse `$response` into the
 * structured `AIInsight`/`AIRecommendation` DTOs; this class does not
 * interpret it.
 */
final class AIResponse
{
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly float $latency,
        public readonly int $tokenUsage,
        public readonly string $response,
    ) {
    }
}
