<?php

declare(strict_types=1);

namespace DMF\Import\Template;

/**
 * A named, versioned import layout — Task T2.2's "per-academic-year import
 * template registry" (docs/02-System-Architecture.md §7). Bundles what a
 * specific source file's structure requires: which headers map to which
 * canonical fields, which fields are mandatory, and what content each field
 * must satisfy.
 *
 * This is metadata only — it does not itself parse, map, or validate
 * anything; ParserInterface/MappingInterface/ValidatorInterface
 * implementations consume it.
 */
final class ImportTemplate
{
    /**
     * @param string $key Registry lookup key, e.g. "ONET-2569".
     * @param string $mappingVersion Free-form version label for the header-alias set
     *     this template uses — lets two templates share a $key's assessment type
     *     while tracking that their column layout differs (e.g. สทศ changed a
     *     header between academic years).
     * @param ColumnMapping $mapping The canonical-field ↔ header-alias dictionary.
     * @param string[] $requiredColumns Canonical field names every row must have a non-empty value for.
     * @param string[] $optionalColumns Canonical field names a row may omit.
     * @param array<string, string> $validationRules Canonical field name =>
     *     Dmf\Core\Validation\Validator rule string (e.g. 'required|int_range:0,100').
     */
    public function __construct(
        public readonly string $key,
        public readonly string $mappingVersion,
        public readonly ColumnMapping $mapping,
        public readonly array $requiredColumns,
        public readonly array $optionalColumns,
        public readonly array $validationRules,
    ) {
    }
}
