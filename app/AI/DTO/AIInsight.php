<?php

declare(strict_types=1);

namespace DMF\AI\DTO;

/**
 * The structured result of one InsightEngine run. Pure data — no
 * calculation happened to produce these fields; they are whatever the
 * AIProviderInterface's response contained, parsed as-is
 * (app/AI/Insight/InsightEngine.php never computes a number itself).
 */
final class AIInsight
{
    /**
     * @param string[] $strengths
     * @param string[] $weaknesses
     * @param string[] $risks
     */
    public function __construct(
        public readonly string $summary,
        public readonly array $strengths,
        public readonly array $weaknesses,
        public readonly array $risks,
        public readonly float $confidence,
    ) {
    }
}
