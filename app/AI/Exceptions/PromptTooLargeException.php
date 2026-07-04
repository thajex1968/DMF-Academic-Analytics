<?php

declare(strict_types=1);

namespace DMF\AI\Exceptions;

/**
 * Thrown when an assembled Prompt exceeds `config/ai.php`'s configured
 * `max_tokens` budget — checked before any AIProviderInterface call is
 * made, so an oversized prompt never reaches (and is never billed by) a
 * real provider.
 */
final class PromptTooLargeException extends AIException
{
}
