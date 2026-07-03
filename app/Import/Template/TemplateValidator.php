<?php

declare(strict_types=1);

namespace DMF\Import\Template;

use DMF\Import\Contracts\ValidatorInterface;
use Dmf\Core\Validation\ValidationResult;
use Dmf\Core\Validation\Validator;

/**
 * Validates mapped rows against an ImportTemplate: every `requiredColumns`
 * entry must be present and non-empty, and every `validationRules` entry
 * runs through `Dmf\Core\Validation\Validator` (Shared Components —
 * docs/Architecture-Principles.md §4 — reusing dmf-core's validator rather
 * than inventing a parallel rule engine).
 *
 * This is structural/declarative validation only — it does not know what a
 * "score" or "student" means; cross-referencing against the database (e.g.
 * "is this a real student_id") is FR-006's content validation, a later,
 * separate task.
 */
final class TemplateValidator implements ValidatorInterface
{
    public function __construct(private readonly Validator $validator = new Validator())
    {
    }

    public function validate(array $mappedRows, ImportTemplate $template): array
    {
        $results = [];

        foreach ($mappedRows as $index => $row) {
            $result = new ValidationResult();

            foreach ($template->requiredColumns as $column) {
                if (!isset($row[$column]) || trim($row[$column]) === '') {
                    $result->addError($column, sprintf('"%s" is required but missing or empty.', $column));
                }
            }

            if ($template->validationRules !== []) {
                $ruleResult = $this->validator->validate($row, $template->validationRules);

                foreach ($ruleResult->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $result->addError($field, $message);
                    }
                }
            }

            $results[$index] = $result;
        }

        return $results;
    }
}
