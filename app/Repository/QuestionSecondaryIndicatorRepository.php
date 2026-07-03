<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;

/**
 * Data access for `question_secondary_indicators` (docs/03-Database-Design.md
 * §6) — the many-side of a question's optional secondary indicators. Pure
 * data access; resolving each row into a full indicator/standard/strand
 * chain is `DMF\Analytics\Normalization\QuestionStandardResolver`'s job.
 */
final class QuestionSecondaryIndicatorRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'question_secondary_indicators';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        $this->connection->execute(
            'INSERT INTO question_secondary_indicators (question_id, indicator_id) VALUES (?, ?)',
            [$data['question_id'], $data['indicator_id']],
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
            "UPDATE question_secondary_indicators SET {$assignments} WHERE id = ?",
            [...array_values($data), $id],
        );

        return $statement->rowCount() > 0;
    }

    /** Not called by T2.5's normal flow — implemented to satisfy `AbstractRepository`'s contract. */
    public function delete(string|int $id): bool
    {
        $statement = $this->connection->execute(
            'DELETE FROM question_secondary_indicators WHERE id = ?',
            [$id],
        );

        return $statement->rowCount() > 0;
    }

    /** @return array<int, array<string, mixed>> Every secondary indicator link for one question. */
    public function findByQuestion(int $questionId): array
    {
        return $this->findWhere('question_id', $questionId);
    }
}
