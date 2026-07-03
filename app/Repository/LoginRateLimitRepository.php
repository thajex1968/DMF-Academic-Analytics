<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `login_rate_limits` — the MySQL-backed store
 * `DMF\Auth\StaffRateLimiter` (decisions/IDR-010) uses instead of
 * `grade.dmf.ac.th`'s session-backed equivalent. Pure data access; the
 * increment/lock/clear *logic* lives in `StaffRateLimiter`, per
 * `Dmf\Core\Auth\RateLimiter`'s own abstract contract.
 */
final class LoginRateLimitRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'login_rate_limits';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO login_rate_limits (username, failed_attempts, locked_until, updated_at)
             VALUES (?, ?, ?, NOW())',
            [
                $data['username'],
                $data['failed_attempts'] ?? 0,
                $data['locked_until'] ?? null,
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
            "UPDATE login_rate_limits SET {$assignments}, updated_at = NOW() WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM login_rate_limits WHERE id = ?', [$id]);

        return $statement->rowCount() > 0;
    }

    /**
     * Matches the table's own `UNIQUE (username)` key.
     *
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        $row = $this->connection
            ->execute('SELECT * FROM login_rate_limits WHERE username = ? LIMIT 1', [$username])
            ->fetch();

        return $row === false ? null : $row;
    }
}
