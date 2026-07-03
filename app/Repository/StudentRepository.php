<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `students`. Part of the Student & Enrollment module — the
 * only module permitted to query `students`/`student_enrollments` directly
 * (docs/Architecture-Principles.md §3 — Module Isolation).
 *
 * `classroom_id` is a denormalized pointer to the student's most recent
 * `student_enrollments` row (docs/03-Database-Design.md §3). Keeping the two
 * in sync on enrollment is a cross-table operation this Repository
 * deliberately does not perform — that belongs to the "resolve current
 * classroom" service (IMPLEMENTATION_GUIDE.md T1.5), not yet built.
 */
final class StudentRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'students';
    }

    protected function primaryKey(): string
    {
        return 'student_id';
    }

    /**
     * `student_id` is a supplied natural key (not auto-increment) — $data
     * must include it.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO students (student_id, classroom_id, full_name, national_id, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['student_id'],
                $data['classroom_id'],
                $data['full_name'],
                $data['national_id'] ?? null,
                $data['status'] ?? 'active',
            ],
        );

        return (string) $data['student_id'];
    }

    /** @param array<string, mixed> $data */
    public function update(string|int $id, array $data): bool
    {
        $columns = array_keys($data);
        $assignments = implode(', ', array_map(static fn (string $c): string => "{$c} = ?", $columns));

        $statement = $this->connection->execute(
            "UPDATE students SET {$assignments}, updated_at = NOW() WHERE student_id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    /**
     * Hard delete. Per docs/Data-Dictionary.md, a student's lifecycle is
     * normally expressed through `status` transitions and the PII-retention
     * rule (docs/03-Database-Design.md §15) — this method exists to satisfy
     * AbstractRepository's contract, not because normal application flow is
     * expected to call it.
     */
    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM students WHERE student_id = ?', [$id]);

        return $statement->rowCount() > 0;
    }

    /**
     * Classroom roster lookup — the query docs/03-Database-Design.md §12
     * indexes `idx_students_classroom_id` for (FR-002 scoping).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByClassroom(int $classroomId): array
    {
        return $this->findWhere('classroom_id', $classroomId);
    }
}
