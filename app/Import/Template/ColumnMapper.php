<?php

declare(strict_types=1);

namespace DMF\Import\Template;

use DMF\Import\Contracts\MappingInterface;
use DMF\Import\ParsedFile;

/**
 * Applies a ColumnMapping to a ParsedFile, reshaping positional rows into
 * rows keyed by canonical field name. Pure mechanism — does not judge
 * whether an unmapped header or a missing required field is an error; that
 * is ValidatorInterface's job (TemplateValidator, in this same package).
 */
final class ColumnMapper implements MappingInterface
{
    public function map(ParsedFile $file, ColumnMapping $mapping): MappingResult
    {
        $fieldByColumnIndex = [];
        $unmappedHeaders = [];

        foreach ($file->headers as $columnIndex => $header) {
            $field = $mapping->canonicalFieldFor($header);

            if ($field === null) {
                $unmappedHeaders[] = $header;

                continue;
            }

            $fieldByColumnIndex[$columnIndex] = $field;
        }

        $mappedRows = [];

        foreach ($file->rows as $row) {
            $mappedRow = [];

            foreach ($fieldByColumnIndex as $columnIndex => $field) {
                $mappedRow[$field] = $row[$columnIndex] ?? '';
            }

            $mappedRows[] = $mappedRow;
        }

        return new MappingResult($mappedRows, $unmappedHeaders);
    }
}
