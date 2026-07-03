<?php

declare(strict_types=1);

namespace DMF\Import\Contracts;

use DMF\Import\ParsedFile;
use RuntimeException;

/**
 * Reads a staged import file into a ParsedFile. Implementations are pure
 * mechanism — file format access only, no business decisions (matches the
 * "data access only" discipline docs/Architecture-Principles.md §3 applies
 * to repositories, one layer up).
 *
 * Implemented by ExcelParser, CsvParser today; a future JsonParser/XmlParser
 * implements the same contract rather than inventing a parallel one.
 */
interface ParserInterface
{
    /** @throws RuntimeException If the file cannot be read or parsed. */
    public function parse(string $filePath): ParsedFile;
}
