<?php

declare(strict_types=1);

namespace DMF\Import;

/**
 * The raw, unvalidated output of a Parser — FR-003's "staged, unvalidated
 * intermediate record set." Rows are plain string arrays; interpreting them
 * as scores/students (structural validation, standard mapping, commit) is
 * later tasks (T2.3+), not this one.
 */
final class ParsedFile
{
    /**
     * @param string[] $headers The first row, as read from the file, trimmed.
     * @param array<int, string[]> $rows Every row after the header, in file order.
     */
    public function __construct(
        public readonly array $headers,
        public readonly array $rows,
    ) {
    }
}
