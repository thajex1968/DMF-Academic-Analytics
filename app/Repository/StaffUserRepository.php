<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `staff_users`. Pure data access — deciding whether a
 * fetched row represents a usable account (active, not soft-deleted,
 * correct password) is `DMF\Auth\StaffTokenManager`'s job, not this class's;
 * `findByUsername()` returns any matching row regardless of status.
 */
final class StaffUserRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'staff_users';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO staff_users
                (username, password_hash, display_name, role, school_id, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['username'],
                $data['password_hash'],
                $data['display_name'],
                $data['role'],
                $data['school_id'],
                $data['is_active'] ?? 1,
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
            "UPDATE staff_users SET {$assignments}, updated_at = NOW() WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    /**
     * Soft delete (`deleted_at`), matching docs/03-Database-Design.md §3's
     * "Soft delete on account deactivation" — never a hard `DELETE`.
     */
    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute(
            'UPDATE staff_users SET deleted_at = NOW() WHERE id = ?',
            [$id],
        );

        return $statement->rowCount() > 0;
    }

    /**
     * Matches the table's own `uq_staff_users_username` unique key.
     *
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        $row = $this->connection
            ->execute('SELECT * FROM staff_users WHERE username = ? LIMIT 1', [$username])
            ->fetch();

        return $row === false ? null : $row;
    }
}
