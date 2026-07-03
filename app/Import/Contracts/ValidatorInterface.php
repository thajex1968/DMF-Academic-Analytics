<?php

declare(strict_types=1);

namespace DMF\Import\Contracts;

use DMF\Import\Template\ImportTemplate;
use Dmf\Core\Validation\ValidationResult;

/**
 * Validates mapped rows against an ImportTemplate's required columns and
 * declarative validation rules. Distinct from FileValidationService (which
 * checks the *file* before parsing) — this checks *content*, once parsed
 * and mapped, against a specific template's requirements.
 *
 * Reuses `Dmf\Core\Validation\ValidationResult` directly (Shared
 * Components — docs/Architecture-Principles.md §4) rather than inventing a
 * parallel result type.
 */
interface ValidatorInterface
{
    /**
     * @param array<int, array<string, string>> $mappedRows
     * @return array<int, ValidationResult> One result per row, keyed by the same index as $mappedRows.
     */
    public function validate(array $mappedRows, ImportTemplate $template): array;
}
