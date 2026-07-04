<?php

declare(strict_types=1);

namespace DMF\Repository;

use Dmf\Core\Database\Repository\AbstractRepository;
use LogicException;

/**
 * Read-only data access for the Dashboard Data API (Sprint 4 Phase 3,
 * decisions/IDR-011 §5/§6) — the one genuinely new read this phase needs:
 * a question's imported responses, joined by `assessment_id` (a column
 * `student_question_responses` itself does not carry). Never writes;
 * `create()`/`update()`/`delete()` throw rather than silently succeeding,
 * since a genuine write attempt against a read model is a programming
 * error, not a valid call this class should ever satisfy.
 */
final class AnalyticsReadRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'student_question_responses';
    }

    protected function primaryKey(): string
    {
        return 'id';
    }

    /**
     * Every `student_question_responses` row for one assessment's
     * questions — the shape `DMF\Analytics\Normalization\ItemIndicatorNormalizer::normalize()`
     * expects (`student_id`, `question_id`, `selected_choice`, `is_correct`).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findResponsesForAssessment(int $assessmentId): array
    {
        return $this->connection
            ->execute(
                'SELECT sqr.student_id, sqr.question_id, sqr.selected_choice, sqr.is_correct
                 FROM student_question_responses sqr
                 JOIN questions q ON q.id = sqr.question_id
                 WHERE q.assessment_id = ?',
                [$assessmentId],
            )
            ->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): string|int
    {
        throw new LogicException('AnalyticsReadRepository is read-only.');
    }

    /** @param array<string, mixed> $data */
    public function update(string|int $id, array $data): bool
    {
        throw new LogicException('AnalyticsReadRepository is read-only.');
    }

    public function delete(string|int $id): bool
    {
        throw new LogicException('AnalyticsReadRepository is read-only.');
    }
}
