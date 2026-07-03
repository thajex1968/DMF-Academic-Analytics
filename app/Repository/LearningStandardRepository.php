<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `learning_standards` (มาตรฐานการเรียนรู้ —
 * docs/03-Database-Design.md §5). Pure data access; the standards catalogue
 * itself is Approval-Flow-entered (docs/01-PRD.md §21), never written by
 * this module.
 */
final class LearningStandardRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'learning_standards';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO learning_standards (strand_id, standard_code, standard_name_th) VALUES (?, ?, ?)',
            [$data['strand_id'], $data['standard_code'], $data['standard_name_th']],
        );

        return (int) $this->connection->pdo()->lastInsertId();
    }

    /**
     * Not called by T2.5's normal flow — implemented to satisfy
     * `AbstractRepository`'s contract.
     *
     * @param array<string, mixed> $data
     */
    public function update(string|int $id, array $data): bool
    {
        $columns = array_keys($data);
        $assignments = implode(', ', array_map(static fn (string $c): string => "{$c} = ?", $columns));

        $statement = $this->connection->execute(
            "UPDATE learning_standards SET {$assignments} WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    /** Not called by T2.5's normal flow — implemented to satisfy `AbstractRepository`'s contract. */
    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM learning_standards WHERE id = ?', [$id]);

        return $statement->rowCount() > 0;
    }
}
