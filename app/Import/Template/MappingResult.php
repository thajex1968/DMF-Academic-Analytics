<?php

declare(strict_types=1);

namespace DMF\Import\Template;

/**
 * The result of applying a ColumnMapping to a ParsedFile's headers/rows.
 */
final class MappingResult
{
    /**
     * @param array<int, array<string, string>> $mappedRows Each row, keyed by canonical field name.
     * @param string[] $unmappedHeaders Header text that matched no known alias — surfaced for the
     *     caller to decide what to do (this class makes no judgment call about whether that's an error).
     */
    public function __construct(
        public readonly array $mappedRows,
        public readonly array $unmappedHeaders,
    ) {
    }
}
