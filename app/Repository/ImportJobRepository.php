<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `import_jobs`. Part of the Import module
 * (docs/02-System-Architecture.md §3). Pure data access — job-state
 * transition rules (queued → processing → committed/failed) live in
 * `DMF\Import\ImportJobManager`, not here.
 */
final class ImportJobRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'import_jobs';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO import_jobs
                (school_id, assessment_id, file_path, file_type, status, uploaded_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['school_id'],
                $data['assessment_id'],
                $data['file_path'],
                $data['file_type'],
                $data['status'] ?? 'queued',
                $data['uploaded_by'],
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
            "UPDATE import_jobs SET {$assignments}, updated_at = NOW() WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM import_jobs WHERE id = ?', [$id]);

        return $statement->rowCount() > 0;
    }

    /**
     * Duplicate-file detection (FR-007) — matches the table's own
     * `uq_import_jobs_school_assessment_file` unique key.
     *
     * @return array<string, mixed>|null
     */
    public function findBySchoolAssessmentAndPath(int $schoolId, int $assessmentId, string $filePath): ?array
    {
        $row = $this->connection
            ->execute(
                'SELECT * FROM import_jobs WHERE school_id = ? AND assessment_id = ? AND file_path = ? LIMIT 1',
                [$schoolId, $assessmentId, $filePath],
            )
            ->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Cron polling target — docs/02-System-Architecture.md §7:
     * "Cron->>Job: SELECT next status='queued'". Written as its own SQL
     * (not delegated to the inherited `findWhere()`, which has no `ORDER
     * BY`) so the result is deterministically FIFO — DMF\Import\Cron\ImportJobRunner
     * (T2.7) depends on earlier uploads processing before later ones.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findQueued(): array
    {
        $statement = $this->connection->execute(
            'SELECT * FROM import_jobs WHERE status = ? ORDER BY created_at ASC, id ASC',
            ['queued'],
        );

        return $statement->fetchAll();
    }

    /**
     * Import History (T2.4) — every job a school has ever submitted, most recent first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findBySchool(int $schoolId): array
    {
        $statement = $this->connection->execute(
            'SELECT * FROM import_jobs WHERE school_id = ? ORDER BY created_at DESC, id DESC',
            [$schoolId],
        );

        return $statement->fetchAll();
    }

    /**
     * "Duplicate import job" detection (T2.6, FR-007) — other jobs still in flight
     * (`queued`/`processing`) for the same school+assessment, which would race or duplicate this
     * job's eventual commit. `$excludeJobId` omits the job currently asking the question, so a job
     * never reports itself as its own duplicate.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findActiveJobsForSchoolAndAssessment(
        int $schoolId,
        int $assessmentId,
        ?int $excludeJobId = null,
    ): array {
        if ($excludeJobId !== null) {
            $statement = $this->connection->execute(
                "SELECT * FROM import_jobs
                 WHERE school_id = ? AND assessment_id = ? AND status IN ('queued', 'processing') AND id != ?
                 ORDER BY created_at ASC, id ASC",
                [$schoolId, $assessmentId, $excludeJobId],
            );
        } else {
            $statement = $this->connection->execute(
                "SELECT * FROM import_jobs
                 WHERE school_id = ? AND assessment_id = ? AND status IN ('queued', 'processing')
                 ORDER BY created_at ASC, id ASC",
                [$schoolId, $assessmentId],
            );
        }

        return $statement->fetchAll();
    }
}
