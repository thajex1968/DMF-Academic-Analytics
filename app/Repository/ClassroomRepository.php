<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `classrooms`. Part of the Student & Enrollment module
 * (docs/Architecture-Principles.md §3 — Module Isolation).
 */
final class ClassroomRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'classrooms';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO classrooms (school_id, grade_level, room_label, academic_year, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())',
            [
                $data['school_id'],
                $data['grade_level'],
                $data['room_label'],
                $data['academic_year'],
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
            "UPDATE classrooms SET {$assignments}, updated_at = NOW() WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM classrooms WHERE id = ?', [$id]);

        return $statement->rowCount() > 0;
    }

    /**
     * Matches the table's own uniqueness constraint
     * (`uq_classrooms_school_room_year` on `school_id, room_label,
     * academic_year`) — the natural "every classroom at this school, this
     * year" lookup.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findBySchoolAndYear(int $schoolId, int $academicYear): array
    {
        $statement = $this->connection->execute(
            'SELECT * FROM classrooms WHERE school_id = ? AND academic_year = ?',
            [$schoolId, $academicYear],
        );

        return $statement->fetchAll();
    }
}
