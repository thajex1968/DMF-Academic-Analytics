<?php

declare(strict_types=1);

namespace DMF\Import\Template;

/**
 * A canonical-field ↔ header-alias dictionary — FR-004's "matching header
 * text against a configurable alias list (e.g., 'รหัสนักเรียน' /
 * 'เลขประจำตัว' both resolve to student ID)."
 *
 * This is the mechanism only. The full "per-academic-year import template
 * registry" (docs/02-System-Architecture.md §7, Task T2.2 — a DB-backed
 * registry of one ColumnMapping per academic year/assessment type) is not
 * built yet; a caller constructs a ColumnMapping directly for now.
 */
final class ColumnMapping
{
    /** @param array<string, string[]> $fieldAliases Canonical field name => accepted header text variants. */
    public function __construct(private readonly array $fieldAliases)
    {
    }

    /** The canonical field name a raw header resolves to, or null if unrecognized. */
    public function canonicalFieldFor(string $header): ?string
    {
        $normalized = trim($header);

        foreach ($this->fieldAliases as $canonicalField => $aliases) {
            if (in_array($normalized, $aliases, true)) {
                return $canonicalField;
            }
        }

        return null;
    }

    /** @return string[] Every canonical field this mapping knows about. */
    public function canonicalFields(): array
    {
        return array_keys($this->fieldAliases);
    }
}
