<?php

declare(strict_types=1);

namespace DMF\Import\Parser;

use DMF\Import\Contracts\ParserInterface;
use DMF\Import\ParsedFile;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use RuntimeException;

/**
 * Reads a `.xlsx` file via PhpSpreadsheet — decisions/IDR-001-phpspreadsheet-for-excel-import.md.
 *
 * FR-004: "Reads from the first worksheet unless an alternate sheet name is
 * configured for that academic year's template" — the template registry
 * (Task T2.2, `DMF\Import\Template\*`) does not yet carry a sheet-name hint,
 * so this always reads the active (first) worksheet.
 */
final class ExcelParser implements ParserInterface
{
    public function parse(string $filePath): ParsedFile
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException(sprintf('Excel file not found or not readable: %s', $filePath));
        }

        $reader = new Xlsx();
        $reader->setReadDataOnly(true);

        try {
            $spreadsheet = $reader->load($filePath);
        } catch (SpreadsheetReaderException $e) {
            throw new RuntimeException(sprintf('Could not read Excel file: %s', $e->getMessage()), 0, $e);
        }

        $sheet = $spreadsheet->getActiveSheet();

        /** @var array<int, array<int, mixed>> $rows */
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            return new ParsedFile([], []);
        }

        $headerRow = array_shift($rows);
        $headers = array_map(static fn (mixed $cell): string => trim((string) ($cell ?? '')), $headerRow);

        // A genuinely empty worksheet's toArray() is not [] — verified directly
        // against PhpSpreadsheet: it is [[0 => null]], one phantom row/column.
        // Once every header cell is blank and no data rows follow, treat it as
        // truly empty rather than a one-column file with a blank header.
        if ($rows === [] && array_filter($headers, static fn (string $h): bool => $h !== '') === []) {
            return new ParsedFile([], []);
        }

        $dataRows = [];

        foreach ($rows as $row) {
            $stringRow = array_map(static fn (mixed $cell): string => (string) ($cell ?? ''), $row);

            // A fully blank interior row (every cell empty) is a formatting
            // artifact (e.g. an accidental spacer row), not a data row —
            // skipped here for the same reason CsvParser skips a blank CSV
            // line, rather than being reported as a validation failure later.
            if (array_filter($stringRow, static fn (string $cell): bool => $cell !== '') === []) {
                continue;
            }

            $dataRows[] = $stringRow;
        }

        return new ParsedFile($headers, $dataRows);
    }
}
