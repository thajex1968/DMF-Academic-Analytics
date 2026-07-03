<?php

declare(strict_types=1);

namespace DMF\Import\Cron;

use DMF\Import\ImportJobManager;
use DMF\Import\Session\ImportSessionService;
use DMF\Import\Template\TemplateResolver;
use Throwable;

/**
 * The cron-polled job runner (T2.7, decisions/IDR-009): picks up queued
 * import jobs, oldest first (ImportJobRepository::findQueued(), fixed in
 * this task to be deterministically ordered), and runs each through the
 * already-approved ImportSessionService (T2.4) — which delegates unchanged
 * to ScoreImportService (T2.3), now also running Duplicate Detection +
 * Audit Trail (T2.6). This class adds no new pipeline behaviour of its own;
 * it only adds polling, batching, and per-job failure isolation on top of
 * the pipeline that already exists.
 *
 * Bounded batch (`$maxJobsPerRun`, default 10): per docs/01-PRD.md §20's
 * 30-second-per-file NFR and CLAUDE.md's "no long-running workers outside
 * cron" hosting constraint, one invocation must return promptly — an
 * unbounded loop over every currently-queued job has no such guarantee once
 * several files queue up at once. Whatever isn't processed this tick is
 * still `queued` and is picked up by the next one.
 *
 * Per-job isolation: a `Throwable` raised outside ScoreImportService's own
 * try/catch (most likely TemplateResolver failing to find a registered
 * template) is caught here, the job is marked `failed` with the same safe,
 * generic message convention T2.3/T2.6 already established (never the raw
 * exception message), and the runner continues — one bad job never aborts
 * the whole run.
 */
final class ImportJobRunner
{
    private const DEFAULT_MAX_JOBS_PER_RUN = 10;

    public function __construct(
        private readonly ImportJobManager $jobManager,
        private readonly ImportSessionService $sessionService,
        private readonly TemplateResolver $templateResolver,
        private readonly int $maxJobsPerRun = self::DEFAULT_MAX_JOBS_PER_RUN,
    ) {
    }

    public function run(): RunSummary
    {
        $jobs = array_slice($this->jobManager->queuedJobs(), 0, $this->maxJobsPerRun);
        $outcomes = [];

        foreach ($jobs as $job) {
            $outcomes[] = $this->runOne((int) $job['id'], (int) $job['assessment_id']);
        }

        return new RunSummary($outcomes);
    }

    private function runOne(int $importJobId, int $assessmentId): JobOutcome
    {
        try {
            $template = $this->templateResolver->resolveForAssessment($assessmentId);
            $result = $this->sessionService->run($importJobId, $template);

            return new JobOutcome($importJobId, $result->success, $result->committedRows, $result->rowErrors);
        } catch (Throwable) {
            $this->jobManager->markFailed($importJobId, 'Import job could not be processed.');

            return new JobOutcome($importJobId, false, 0, ['Import job could not be processed.']);
        }
    }
}
