<?php

declare(strict_types=1);

namespace DMF\Import\Score;

use DMF\Repository\StudentScoreRepository;
use Dmf\Core\Contract\ConnectionInterface;

/**
 * Commits every row of one import job's resolved, normalized scores inside
 * a single database transaction — FR-006's "no partial commits": a
 * validation/DB failure on any row rolls back the entire batch, never a
 * partial set of committed scores.
 *
 * Wraps `Dmf\Core\Contract\ConnectionInterface::transaction()` directly
 * (already verified real, in `dmf-core`'s own source, during T1.x/T2.1) —
 * does not reimplement transaction handling.
 */
final class ImportTransactionService
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly StudentScoreRepository $scores,
    ) {
    }

    /**
     * @param array<int, array{student_id: string, assessment_id: int, score: float, import_job_id: int}> $rows
     * @return int Number of rows committed.
     */
    public function commit(array $rows): int
    {
        return $this->connection->transaction(function () use ($rows): int {
            $committed = 0;

            foreach ($rows as $row) {
                $this->scores->create($row);
                $committed++;
            }

            return $committed;
        });
    }
}
