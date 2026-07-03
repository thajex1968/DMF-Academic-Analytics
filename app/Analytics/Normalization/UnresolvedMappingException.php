<?php

declare(strict_types=1);

namespace DMF\Analytics\Normalization;

use RuntimeException;

/**
 * Raised by QuestionStandardResolver when any link in the
 * question → indicator → standard → strand chain is missing. Never raised
 * to fabricate a mapping — only to report that one cannot be resolved.
 */
final class UnresolvedMappingException extends RuntimeException
{
}
