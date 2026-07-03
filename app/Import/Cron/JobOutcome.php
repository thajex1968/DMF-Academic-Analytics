<?php

declare(strict_types=1);

namespace DMF\Import\Cron;

/** One queued job's outcome from a single ImportJobRunner::run() pass. */
final class JobOutcome
{
    /** @param string[] $rowErrors */
    public function __construct(
        public readonly int $importJobId,
        public readonly bool $success,
        public readonly int $committedRows,
        public readonly array $rowErrors,
    ) {
    }
}
