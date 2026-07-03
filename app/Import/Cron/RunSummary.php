<?php

declare(strict_types=1);

namespace DMF\Import\Cron;

/** The outcome of one ImportJobRunner::run() invocation — one cron tick's worth of processing. */
final class RunSummary
{
    /** @param JobOutcome[] $outcomes */
    public function __construct(
        public readonly array $outcomes,
    ) {
    }

    public function processedCount(): int
    {
        return count($this->outcomes);
    }

    public function successCount(): int
    {
        return count(array_filter($this->outcomes, static fn (JobOutcome $outcome): bool => $outcome->success));
    }

    public function failureCount(): int
    {
        return $this->processedCount() - $this->successCount();
    }
}
