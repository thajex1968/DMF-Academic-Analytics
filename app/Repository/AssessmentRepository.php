<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `assessments`. Part of the Assessment Framework
 * (docs/03-Database-Design.md §4). Pure data access — confirming an
 * assessment_id is real before committing scores against it is
 * `DMF\Import\Score\AssessmentResolver`'s job, not this class's.
 */
final class AssessmentRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'assessments';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO assessments
                (assessment_type_id, subject_code, grade_level, academic_year, name_th, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['assessment_type_id'],
                $data['subject_code'],
                $data['grade_level'],
                $data['academic_year'],
                $data['name_th'],
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
            "UPDATE assessments SET {$assignments}, updated_at = NOW() WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM assessments WHERE id = ?', [$id]);

        return $statement->rowCount() > 0;
    }

    /**
     * Dashboard Data API (Sprint 4 Phase 3) — the assessment a Dashboard
     * request reports on, since `Dmf\Core\Http\Request` has no way to carry
     * a caller-supplied assessment id (decisions/IDR-011 §2).
     *
     * @return array<string, mixed>|null
     */
    public function findLatest(): ?array
    {
        $row = $this->connection
            ->execute('SELECT * FROM assessments ORDER BY academic_year DESC, id DESC LIMIT 1')
            ->fetch();

        return $row === false ? null : $row;
    }
}
