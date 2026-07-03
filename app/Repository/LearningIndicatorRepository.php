<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `learning_indicators` (ตัวชี้วัด — docs/03-Database-Design.md
 * §5). Pure data access; the standards catalogue itself is Approval-Flow
 * -entered (docs/01-PRD.md §21), never written by this module.
 */
final class LearningIndicatorRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'learning_indicators';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO learning_indicators
                (standard_id, indicator_code, indicator_name_th, grade_level, curriculum_revision)
             VALUES (?, ?, ?, ?, ?)',
            [
                $data['standard_id'],
                $data['indicator_code'],
                $data['indicator_name_th'],
                $data['grade_level'],
                $data['curriculum_revision'] ?? '2560',
            ],
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
            "UPDATE learning_indicators SET {$assignments} WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    /** Not called by T2.5's normal flow — implemented to satisfy `AbstractRepository`'s contract. */
    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute('DELETE FROM learning_indicators WHERE id = ?', [$id]);

        return $statement->rowCount() > 0;
    }
}
