<?php

declare(strict_types=1);

namespace DMF\AI\Recommendation;

use DMF\AI\Contracts\AIProviderInterface;
use DMF\AI\Contracts\PromptBuilderInterface;
use DMF\AI\Contracts\RecommendationGeneratorInterface;
use DMF\AI\DTO\AIContext;
use DMF\AI\DTO\AIRecommendation;
use DMF\AI\DTO\AIRecommendationPriority;
use DMF\AI\DTO\AIResponse;
use DMF\AI\Exceptions\AIException;
use DMF\AI\Exceptions\PromptTooLargeException;
use DMF\AI\Exceptions\UnsupportedProviderException;
use DMF\AI\Prompt\PromptType;
use DMF\Analytics\Dashboard\DashboardAssessment;
use DMF\Analytics\Dashboard\DashboardHealth;
use DMF\Analytics\Dashboard\DashboardSummary;
use JsonException;
use ValueError;

/**
 * PromptBuilder → AIProvider → AIRecommendation. Recommendations only —
 * this class never calculates an average, never calculates a benchmark,
 * never infers a hidden value; `priority`/`category`/`recommendation`/
 * `reason` are whatever the provider's response contained, parsed as-is.
 * Same "insufficient data" PHP-level guard as InsightEngine, for the same
 * reason — see that class's docblock.
 */
final class RecommendationEngine implements RecommendationGeneratorInterface
{
    private const REQUIRED_CAPABILITY = 'recommendation';
    private const INSUFFICIENT_DATA_MESSAGE = 'Insufficient data.';

    /** @param int $maxPromptCharacters see InsightEngine's constructor docblock. */
    public function __construct(
        private readonly PromptBuilderInterface $promptBuilder,
        private readonly AIProviderInterface $provider,
        private readonly int $maxPromptCharacters,
    ) {
    }

    public function generateRecommendation(
        AIContext $context,
        DashboardSummary $summary,
        DashboardHealth $health,
        DashboardAssessment $assessment,
    ): AIRecommendation {
        if ($assessment->percentCorrect === null) {
            return new AIRecommendation(
                AIRecommendationPriority::LOW,
                'data',
                self::INSUFFICIENT_DATA_MESSAGE,
                self::INSUFFICIENT_DATA_MESSAGE,
                0.0,
            );
        }

        if (!in_array(self::REQUIRED_CAPABILITY, $this->provider->capabilities(), true)) {
            throw new UnsupportedProviderException(sprintf(
                'Provider "%s" does not support recommendation generation.',
                $this->provider->providerName(),
            ));
        }

        $prompt = $this->promptBuilder->build(PromptType::RECOMMENDATION, $context, $summary, $health, $assessment);

        if (strlen($prompt->toText()) > $this->maxPromptCharacters) {
            throw new PromptTooLargeException(sprintf(
                'Prompt of %d characters exceeds the configured limit of %d characters.',
                strlen($prompt->toText()),
                $this->maxPromptCharacters,
            ));
        }

        return $this->parseRecommendation($this->provider->generate($prompt));
    }

    private function parseRecommendation(AIResponse $response): AIRecommendation
    {
        try {
            $decoded = json_decode($response->response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new AIException('Provider response was not valid JSON.', 0, $e);
        }

        $hasRequiredFields = is_array($decoded) && isset(
            $decoded['priority'],
            $decoded['category'],
            $decoded['recommendation'],
            $decoded['reason'],
            $decoded['confidence'],
        );

        if (!$hasRequiredFields) {
            throw new AIException('Provider response is missing one or more required Recommendation fields.');
        }

        try {
            $priority = AIRecommendationPriority::from((string) $decoded['priority']);
        } catch (ValueError $e) {
            $message = sprintf('Provider returned an unrecognized priority "%s".', (string) $decoded['priority']);

            throw new AIException($message, 0, $e);
        }

        return new AIRecommendation(
            $priority,
            (string) $decoded['category'],
            (string) $decoded['recommendation'],
            (string) $decoded['reason'],
            (float) $decoded['confidence'],
        );
    }
}
