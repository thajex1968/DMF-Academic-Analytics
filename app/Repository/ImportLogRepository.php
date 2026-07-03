<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `import_logs` — the append-only audit trail per
 * `import_job_id` (FR-008). Part of the Import module. Pure data access;
 * deciding *when* to log an event is `DMF\Import\ImportJobManager`'s job.
 */
final class ImportLogRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'import_logs';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO import_logs (import_job_id, event, message, actor_id, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [
                $data['import_job_id'],
                $data['event'],
                $data['message'] ?? null,
                $data['actor_id'] ?? null,
            ],
        );

        return (int) $this->connection->pdo()->lastInsertId();
    }

    /**
     * `import_logs` is append-only (FR-008) — existing rows are never
     * updated. Implemented to satisfy AbstractRepository's contract only.
     *
     * @param array<string, mixed> $data
     */
    public function update(string|int $id, array $data): bool
    {
        $columns = array_keys($data);
        $assignments = implode(', ', array_map(static fn (string $c): string => "{$c} = ?", $columns));

        $statement = $this->connection->execute(
            "UPDATE import_logs SET {$assignments} WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    /**
     * `import_logs` is append-only (FR-008) — rows are never deleted.
     * Implemented to satisfy AbstractRepository's contract only.
     */
    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM import_logs WHERE id = ?', [$id]);

        return $statement->rowCount() > 0;
    }

    /**
     * The full audit trail for one import job, oldest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByImportJob(int $importJobId): array
    {
        $statement = $this->connection->execute(
            'SELECT * FROM import_logs WHERE import_job_id = ? ORDER BY created_at ASC, id ASC',
            [$importJobId],
        );

        return $statement->fetchAll();
    }
}
