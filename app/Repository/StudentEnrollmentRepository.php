<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `student_enrollments` — the authoritative record of a
 * student's grade/classroom history, one row per academic year
 * (docs/03-Database-Design.md §3). Part of the Student & Enrollment module
 * (docs/Architecture-Principles.md §3 — Module Isolation).
 *
 * This repository does not update `students.classroom_id` as a side effect
 * of create() — keeping that denormalized pointer in sync is a cross-table
 * operation left to the "resolve current classroom" service
 * (IMPLEMENTATION_GUIDE.md T1.5), not yet built. A caller that needs both
 * writes done together is responsible for wrapping them in
 * `Connection::transaction()` itself.
 */
final class StudentEnrollmentRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'student_enrollments';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO student_enrollments
                (student_id, school_id, classroom_id, grade_level, academic_year,
                 enrollment_status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['student_id'],
                $data['school_id'],
                $data['classroom_id'],
                $data['grade_level'],
                $data['academic_year'],
                $data['enrollment_status'] ?? 'active',
            ],
        );

        return (int) $this->connection->pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(string|int $id, array $data): bool
    {
        $columns = array_keys($data);
        $assignments = implode(', ', array_map(static fn (string $c): string => "{$c} = ?", $columns));

        $statement = $this->connection->execute(
            "UPDATE student_enrollments SET {$assignments}, updated_at = NOW() WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM student_enrollments WHERE id = ?', [$id]);

        return $statement->rowCount() > 0;
    }

    /**
     * A student's full Grade 1–6 enrollment history, oldest first — the
     * query docs/03-Database-Design.md §17 gives as the reference "longitudinal"
     * lookup.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByStudent(string $studentId): array
    {
        $statement = $this->connection->execute(
            'SELECT * FROM student_enrollments WHERE student_id = ? ORDER BY academic_year ASC',
            [$studentId],
        );

        return $statement->fetchAll();
    }

    /**
     * Matches the table's own uniqueness constraint
     * (`uq_student_enrollments_student_year` on `student_id, academic_year`)
     * — a student has exactly one enrollment row per academic year.
     *
     * @return array<string, mixed>|null
     */
    public function findByStudentAndYear(string $studentId, int $academicYear): ?array
    {
        $row = $this->connection
            ->execute(
                'SELECT * FROM student_enrollments WHERE student_id = ? AND academic_year = ? LIMIT 1',
                [$studentId, $academicYear],
            )
            ->fetch();

        return $row === false ? null : $row;
    }

    /**
     * The student's most recent enrollment row (highest academic_year) —
     * the row `students.classroom_id` is supposed to mirror
     * (docs/03-Database-Design.md §11).
     *
     * @return array<string, mixed>|null
     */
    public function findCurrentForStudent(string $studentId): ?array
    {
        $row = $this->connection
            ->execute(
                'SELECT * FROM student_enrollments WHERE student_id = ? ORDER BY academic_year DESC LIMIT 1',
                [$studentId],
            )
            ->fetch();

        return $row === false ? null : $row;
    }
}
