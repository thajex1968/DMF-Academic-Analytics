<?php

declare(strict_types=1);

namespace DMF\Import\Session;

/**
 * One traceable, user-facing row-level error: which file row, what went
 * wrong. `$rowNumber === 0` marks a whole-file error (e.g. "assessment not
 * found") that ImportResult never attributed to a specific row.
 */
final class RowError
{
    public function __construct(
        public readonly int $rowNumber,
        public readonly string $message,
    ) {
    }
}
