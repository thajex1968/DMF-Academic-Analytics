<?php

declare(strict_types=1);

namespace DMF\AI\Insight;

use DMF\AI\Contracts\AIProviderInterface;
use DMF\AI\Contracts\InsightGeneratorInterface;
use DMF\AI\Contracts\PromptBuilderInterface;
use DMF\AI\DTO\AIContext;
use DMF\AI\DTO\AIInsight;
use DMF\AI\DTO\AIResponse;
use DMF\AI\Exceptions\AIException;
use DMF\AI\Exceptions\PromptTooLargeException;
use DMF\AI\Exceptions\UnsupportedProviderException;
use DMF\AI\Prompt\PromptType;
use DMF\Analytics\Dashboard\DashboardAssessment;
use DMF\Analytics\Dashboard\DashboardHealth;
use DMF\Analytics\Dashboard\DashboardSummary;
use JsonException;

/**
 * PromptBuilder → AIProvider → AIInsight. No business calculation, no
 * analytics, no SQL — every field returned came from the provider's own
 * response, parsed as-is. If `$assessment->percentCorrect` is `null` (no
 * computable data), this never even builds a prompt or calls the
 * provider — the safety rule ("if analytics are missing, output
 * 'Insufficient data', never guess") is enforced here in PHP, not left to
 * a prompt instruction the provider might not follow.
 */
final class InsightEngine implements InsightGeneratorInterface
{
    private const REQUIRED_CAPABILITY = 'insight';
    private const INSUFFICIENT_DATA_SUMMARY = 'Insufficient data.';

    /**
     * @param int $maxPromptCharacters A character-count budget approximating
     *     `config/ai.php`'s `max_tokens` (this codebase has no tokenizer; the
     *     caller wiring this class is responsible for the token→character
     *     conversion — kept out of this class to keep it config-agnostic).
     */
    public function __construct(
        private readonly PromptBuilderInterface $promptBuilder,
        private readonly AIProviderInterface $provider,
        private readonly int $maxPromptCharacters,
    ) {
    }

    public function generateInsight(
        AIContext $context,
        DashboardSummary $summary,
        DashboardHealth $health,
        DashboardAssessment $assessment,
    ): AIInsight {
        if ($assessment->percentCorrect === null) {
            return new AIInsight(self::INSUFFICIENT_DATA_SUMMARY, [], [], [], 0.0);
        }

        if (!in_array(self::REQUIRED_CAPABILITY, $this->provider->capabilities(), true)) {
            throw new UnsupportedProviderException(sprintf(
                'Provider "%s" does not support insight generation.',
                $this->provider->providerName(),
            ));
        }

        $prompt = $this->promptBuilder->build(PromptType::INSIGHT, $context, $summary, $health, $assessment);

        if (strlen($prompt->toText()) > $this->maxPromptCharacters) {
            throw new PromptTooLargeException(sprintf(
                'Prompt of %d characters exceeds the configured limit of %d characters.',
                strlen($prompt->toText()),
                $this->maxPromptCharacters,
            ));
        }

        return $this->parseInsight($this->provider->generate($prompt));
    }

    private function parseInsight(AIResponse $response): AIInsight
    {
        try {
            $decoded = json_decode($response->response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new AIException('Provider response was not valid JSON.', 0, $e);
        }

        $hasRequiredFields = is_array($decoded) && isset(
            $decoded['summary'],
            $decoded['strengths'],
            $decoded['weaknesses'],
            $decoded['risks'],
            $decoded['confidence'],
        );

        if (!$hasRequiredFields) {
            throw new AIException('Provider response is missing one or more required Insight fields.');
        }

        return new AIInsight(
            (string) $decoded['summary'],
            array_map('strval', (array) $decoded['strengths']),
            array_map('strval', (array) $decoded['weaknesses']),
            array_map('strval', (array) $decoded['risks']),
            (float) $decoded['confidence'],
        );
    }
}
