<?php

declare(strict_types=1);

namespace DMF\Import\Parser;

use DMF\Import\Contracts\ParserInterface;
use DMF\Import\ParsedFile;
use RuntimeException;

/**
 * Reads a `.csv` file with configurable delimiter and UTF-8/TIS-620
 * encoding auto-detection (FR-005).
 *
 * Uses `iconv()`, not `mb_convert_encoding()` — verified directly against
 * this environment's PHP build that mbstring does not register "TIS-620" as
 * a valid encoding name (`mb_list_encodings()` does not include it), while
 * `iconv()` converts it correctly (round-trip verified during T2.1).
 *
 * Quoted fields containing embedded delimiters are handled by `fgetcsv()`
 * itself — that is standard CSV-quoting behavior, not something this class
 * implements separately.
 */
final class CsvParser implements ParserInterface
{
    /** Windows/DOS Thai codepage — practically interchangeable with TIS-620 for this content. */
    private const ANSI_THAI_ENCODING = 'CP874';

    public function __construct(private readonly string $delimiter = ',')
    {
    }

    public function parse(string $filePath): ParsedFile
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException(sprintf('CSV file not found or not readable: %s', $filePath));
        }

        $raw = file_get_contents($filePath);

        if ($raw === false) {
            throw new RuntimeException(sprintf('Could not read CSV file: %s', $filePath));
        }

        $utf8 = $this->normalizeToUtf8($raw);

        $handle = fopen('php://temp', 'r+b');

        if ($handle === false) {
            throw new RuntimeException('Could not open a temporary stream to parse the CSV content.');
        }

        fwrite($handle, $utf8);
        rewind($handle);

        $headers = [];
        $rows = [];
        $isFirstRow = true;

        while (($row = fgetcsv($handle, 0, $this->delimiter)) !== false) {
            // A completely blank line is returned by fgetcsv() as [0 => null],
            // not a row of empty strings — verified directly against this PHP
            // build. Treated as a formatting artifact (e.g. trailing EOF
            // newline), not a data row, and skipped.
            if ($row === [null]) {
                continue;
            }

            /** @var string[] $row */
            if ($isFirstRow) {
                $headers = array_map(static fn (string $cell): string => trim($cell), $row);
                $isFirstRow = false;

                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return new ParsedFile($headers, $rows);
    }

    /** UTF-8 passes through unchanged; anything else is treated as ANSI Thai (CP874/TIS-620) and converted. */
    private function normalizeToUtf8(string $content): string
    {
        if ($content === '' || mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        $converted = @iconv(self::ANSI_THAI_ENCODING, 'UTF-8', $content);

        return $converted === false ? $content : $converted;
    }
}
