<?php

declare(strict_types=1);

namespace DMF\AI\Exceptions;

use RuntimeException;

/**
 * Base type for every AI Foundation failure — catchable as one type by a
 * caller that doesn't need to distinguish which specific AI failure
 * occurred, matching `dmf-core`'s own exception-hierarchy convention
 * (`ValidationException`/`AuthException`/`DatabaseException`).
 */
class AIException extends RuntimeException
{
}
