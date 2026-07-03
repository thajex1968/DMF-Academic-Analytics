<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `teacher_classrooms`. Part of the Student & Enrollment
 * module (docs/Architecture-Principles.md §3 — Module Isolation).
 *
 * Note the table has no `updated_at` column (docs/03-Database-Design.md §3)
 * — update() below does not set one.
 */
final class TeacherClassroomRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'teacher_classrooms';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO teacher_classrooms (staff_user_id, classroom_id, is_current, created_at)
             VALUES (?, ?, ?, NOW())',
            [
                $data['staff_user_id'],
                $data['classroom_id'],
                $data['is_current'] ?? 1,
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
            "UPDATE teacher_classrooms SET {$assignments} WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM teacher_classrooms WHERE id = ?', [$id]);

        return $statement->rowCount() > 0;
    }

    /** @return array<int, array<string, mixed>> */
    public function findByStaffUser(int $staffUserId): array
    {
        return $this->findWhere('staff_user_id', $staffUserId);
    }

    /**
     * The teacher's active assignment(s) for the current academic year —
     * `is_current = 1`, mirroring `dmf_grade.teacher_classroom_history`
     * (docs/03-Database-Design.md §3).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findCurrentByStaffUser(int $staffUserId): array
    {
        $statement = $this->connection->execute(
            'SELECT * FROM teacher_classrooms WHERE staff_user_id = ? AND is_current = 1',
            [$staffUserId],
        );

        return $statement->fetchAll();
    }

    /**
     * Sets every currently-active row for this teacher to `is_current = 0`.
     * Pure mechanism, not policy — deciding *when* to call this (e.g. before
     * inserting this teacher's new-year assignment) is the caller's
     * responsibility, per docs/Data-Dictionary.md's note that "the
     * application, not a database constraint, is responsible for flipping
     * the prior year's row to 0 when a new one is created."
     *
     * @return int Rows affected.
     */
    public function clearCurrentForStaffUser(int $staffUserId): int
    {
        $statement = $this->connection->execute(
            'UPDATE teacher_classrooms SET is_current = 0 WHERE staff_user_id = ? AND is_current = 1',
            [$staffUserId],
        );

        return $statement->rowCount();
    }
}
