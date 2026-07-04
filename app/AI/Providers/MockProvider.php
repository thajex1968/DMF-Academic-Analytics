<?php

declare(strict_types=1);

namespace DMF\AI\Providers;

use DMF\AI\Contracts\AIProviderInterface;
use DMF\AI\DTO\AIResponse;
use DMF\AI\Prompt\Prompt;
use DMF\AI\Prompt\PromptType;
use JsonException;

/**
 * The only AIProviderInterface implementation this Sprint ships — used by
 * PHPUnit only. Deterministic: the same Prompt always produces the exact
 * same AIResponse, no `rand()`/`random_bytes()`, no `time()`/`microtime()`,
 * no network call of any kind. Sprint 6 Phase 2 (not built) is where a real
 * provider (HTTP client + a real LLM) would be added behind this same
 * interface.
 */
final class MockProvider implements AIProviderInterface
{
    private const LATENCY_SECONDS = 0.0;
    private const TOKEN_USAGE = 128;

    public function generate(Prompt $prompt): AIResponse
    {
        return new AIResponse(
            $this->providerName(),
            $this->model(),
            self::LATENCY_SECONDS,
            self::TOKEN_USAGE,
            $this->deterministicResponseFor($prompt),
        );
    }

    public function health(): bool
    {
        return true;
    }

    public function providerName(): string
    {
        return 'mock';
    }

    public function model(): string
    {
        return 'mock-model-v1';
    }

    /** @return string[] */
    public function capabilities(): array
    {
        return ['insight', 'recommendation'];
    }

    private function deterministicResponseFor(Prompt $prompt): string
    {
        try {
            if ($prompt->type === PromptType::INSIGHT) {
                return json_encode([
                    'summary' => 'Mock insight summary for automated testing.',
                    'strengths' => ['Mock strength for automated testing.'],
                    'weaknesses' => ['Mock weakness for automated testing.'],
                    'risks' => ['Mock risk for automated testing.'],
                    'confidence' => 0.5,
                ], JSON_THROW_ON_ERROR);
            }

            return json_encode([
                'priority' => 'medium',
                'category' => 'mock-category',
                'recommendation' => 'Mock recommendation for automated testing.',
                'reason' => 'Mock reason for automated testing.',
                'confidence' => 0.5,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // Unreachable with the fixed literal arrays above; satisfies PHPStan's
            // knowledge that json_encode can return false without JSON_THROW_ON_ERROR.
            return '{}';
        }
    }
}
