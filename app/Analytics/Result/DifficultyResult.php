<?php

declare(strict_types=1);

namespace DMF\Analytics\Result;

/**
 * One question's CTT difficulty index (p-value) — the proportion of
 * recorded responses that were correct. `standardId` is the question's
 * primary standard only, matching QuestionAnalyticsRecord.
 */
final class DifficultyResult
{
    public function __construct(
        public readonly int $questionId,
        public readonly int $standardId,
        public readonly float $difficultyIndex,
    ) {
    }
}
