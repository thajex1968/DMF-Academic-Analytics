<?php

declare(strict_types=1);

namespace DMF\AI\Exceptions;

/**
 * Thrown when the configured AIProviderInterface's own declared
 * `capabilities()` do not include the operation an engine was asked to
 * perform (e.g. a provider that only supports `insight` asked to generate
 * a `recommendation`) — checked before `generate()` is called.
 */
final class UnsupportedProviderException extends AIException
{
}
