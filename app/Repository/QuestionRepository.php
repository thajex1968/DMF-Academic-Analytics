<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `questions` (docs/03-Database-Design.md §6). Pure data
 * access — resolving a question's indicator/standard/strand chain is
 * `DMF\Analytics\Normalization\QuestionStandardResolver`'s job, not this
 * class's. No `created_at`/`updated_at` columns exist on this table.
 */
final class QuestionRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'questions';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO questions (assessment_id, item_number, primary_indicator_id, correct_choice)
             VALUES (?, ?, ?, ?)',
            [
                $data['assessment_id'],
                $data['item_number'],
                $data['primary_indicator_id'],
                $data['correct_choice'] ?? null,
            ],
        );

        return (int) $this->connection->pdo()->lastInsertId();
    }

    /**
     * Not called by T2.5's normal flow (read-only against an already-seeded
     * catalogue) — implemented to satisfy `AbstractRepository`'s contract.
     *
     * @param array<string, mixed> $data
     */
    public function update(string|int $id, array $data): bool
    {
        $columns = array_keys($data);
        $assignments = implode(', ', array_map(static fn (string $c): string => "{$c} = ?", $columns));

        $statement = $this->connection->execute(
            "UPDATE questions SET {$assignments} WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    /** Not called by T2.5's normal flow — implemented to satisfy `AbstractRepository`'s contract. */
    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM questions WHERE id = ?', [$id]);

        return $statement->rowCount() > 0;
    }
}
