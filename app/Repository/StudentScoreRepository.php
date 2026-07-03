<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `student_scores`. Committed rows are immutable
 * (docs/03-Database-Design.md §13) — a correction is a new `import_job`
 * whose rows supersede the prior ones logically, never an `UPDATE`/`DELETE`
 * of a committed row. Pure data access; deciding *whether* a row is safe to
 * insert (student/assessment resolved, score normalized and validated) is
 * `DMF\Import\Score\*`'s job, not this class's.
 */
final class StudentScoreRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'student_scores';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO student_scores (student_id, assessment_id, score, import_job_id, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [
                $data['student_id'],
                $data['assessment_id'],
                $data['score'],
                $data['import_job_id'],
            ],
        );

        return (int) $this->connection->pdo()->lastInsertId();
    }

    /**
     * Committed scores are immutable — implemented to satisfy
     * AbstractRepository's contract only; normal application flow never
     * calls this.
     *
     * @param array<string, mixed> $data
     */
    public function update(string|int $id, array $data): bool
    {
        $columns = array_keys($data);
        $assignments = implode(', ', array_map(static fn (string $c): string => "{$c} = ?", $columns));

        $statement = $this->connection->execute(
            "UPDATE student_scores SET {$assignments} WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    /**
     * Committed scores are immutable — implemented to satisfy
     * AbstractRepository's contract only; normal application flow never
     * calls this.
     */
    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM student_scores WHERE id = ?', [$id]);

        return $statement->rowCount() > 0;
    }

    /**
     * Matches the table's own uniqueness constraint
     * (`uq_student_scores_student_assessment`) — a student has at most one
     * committed score per assessment (FR-007).
     */
    public function existsForStudentAndAssessment(string $studentId, int $assessmentId): bool
    {
        $statement = $this->connection->execute(
            'SELECT 1 FROM student_scores WHERE student_id = ? AND assessment_id = ? LIMIT 1',
            [$studentId, $assessmentId],
        );

        return $statement->fetch() !== false;
    }
}
