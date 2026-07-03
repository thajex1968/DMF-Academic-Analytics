<?php

declare(strict_types=1);

namespace DMF\Import\Score;

use DMF\Import\Contracts\ValidatorInterface;
use DMF\Import\Template\ImportTemplate;
use Dmf\Core\Validation\ValidationResult;

/**
 * Score-Import-specific row validation: delegates template-rule checking to
 * the injected ValidatorInterface (TemplateValidator, reused per
 * instruction), then adds one cross-row rule TemplateValidator cannot
 * express on its own — no two rows in the *same file* may claim the same
 * student_id. This is a within-file check only; a student who already has a
 * committed score from an *earlier* import (FR-007) is a different concern,
 * checked separately (StudentScoreRepository::existsForStudentAndAssessment()).
 */
final class RowValidator
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @param array<int, array<string, string>> $mappedRows
     * @return array<int, ValidationResult> Keyed by the same row index as $mappedRows.
     */
    public function validate(array $mappedRows, ImportTemplate $template): array
    {
        $results = $this->validator->validate($mappedRows, $template);

        /** @var array<string, int> $firstSeenAtRow */
        $firstSeenAtRow = [];

        foreach ($mappedRows as $index => $row) {
            $studentId = $row['student_id'] ?? '';

            if ($studentId === '') {
                continue;
            }

            if (isset($firstSeenAtRow[$studentId])) {
                $results[$index]->addError(
                    'student_id',
                    sprintf(
                        'Duplicate student_id "%s" within this file (first seen at row %d).',
                        $studentId,
                        $firstSeenAtRow[$studentId],
                    ),
                );

                continue;
            }

            $firstSeenAtRow[$studentId] = $index;
        }

        return $results;
    }
}
