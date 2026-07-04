<?php

declare(strict_types=1);

namespace DMF\AI\Contracts;

use DMF\AI\DTO\AIResponse;
use DMF\AI\Prompt\Prompt;

/**
 * The one boundary between the AI Foundation Layer and an actual AI
 * provider (Sprint 6 Phase 2, not built yet — MockProvider is the only
 * implementation this phase ships). Deliberately thin: a provider is a
 * prompt-in/response-out transport, never an HTTP client, SDK, or curl
 * handle exposed to a caller — "never expose HTTP implementation" per this
 * Sprint's instruction.
 */
interface AIProviderInterface
{
    public function generate(Prompt $prompt): AIResponse;

    public function health(): bool;

    public function providerName(): string;

    public function model(): string;

    /** @return string[] */
    public function capabilities(): array;
}
