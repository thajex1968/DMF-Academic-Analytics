<?php

declare(strict_types=1);

/**
 * AI Foundation Layer configuration (Sprint 6 Phase 1).
 *
 * All secrets (a future real provider's API key) come from environment
 * variables only — never hardcoded here — matching config/auth.php's
 * existing discipline. No real provider exists yet (Sprint 6 Phase 2, not
 * built); 'default_provider' defaults to 'mock', the only
 * AIProviderInterface implementation this phase ships.
 */
return [
    'default_provider' => getenv('AI_DEFAULT_PROVIDER') ?: 'mock',
    'timeout'          => (int) (getenv('AI_TIMEOUT') ?: 30),
    'temperature'      => (float) (getenv('AI_TEMPERATURE') ?: 0.2),
    'max_tokens'       => (int) (getenv('AI_MAX_TOKENS') ?: 2048),
    'retry'            => (int) (getenv('AI_RETRY') ?: 0),
    'prompt_version'   => getenv('AI_PROMPT_VERSION') ?: 'v1',
];
