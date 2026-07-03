<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `schools`. Minimal — the dashboard shell (T1.7) needs only
 * `findById()` (inherited from `AbstractRepository`) to resolve a
 * logged-in user's `school_id` claim to a display name.
 */
final class SchoolRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'schools';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO schools (school_code, name_th, province, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())',
            [$data['school_code'], $data['name_th'], $data['province']],
        );

        return (int) $this->connection->pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(string|int $id, array $data): bool
    {
        $columns = array_keys($data);
        $assignments = implode(', ', array_map(static fn (string $c): string => "{$c} = ?", $columns));

        $statement = $this->connection->execute(
            "UPDATE schools SET {$assignments}, updated_at = NOW() WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM schools WHERE id = ?', [$id]);

        return $statement->rowCount() > 0;
    }
}
