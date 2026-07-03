<?php

declare(strict_types=1);

namespace DMF\Import\Score;

use DMF\Repository\StudentRepository;

/**
 * Confirms a `student_id` from a parsed row is a real, known student
 * (FR-006: "referential integrity against known students and items").
 * Read-only — never creates a student from import data; an unrecognized
 * student_id is a validation failure, not something this pipeline resolves
 * by inventing a record.
 */
final class StudentResolver
{
    public function __construct(private readonly StudentRepository $students)
    {
    }

    /** @return array<string, mixed>|null Null if no student exists with this id. */
    public function resolve(string $studentId): ?array
    {
        return $this->students->findById($studentId);
    }
}
