<?php

declare(strict_types=1);

namespace DMF\Import;

use DMF\Repository\ImportJobRepository;
use DMF\Repository\ImportLogRepository;

/**
 * Owns the `import_jobs` state machine (docs/02-System-Architecture.md §7):
 * queued → processing → committed | failed. Every transition is paired with
 * an `import_logs` entry — this is the audit trail FR-008 requires.
 *
 * Business logic (the Service layer, per docs/Architecture-Principles.md §3
 * — Module Isolation); the two repositories it wraps stay pure data access.
 */
final class ImportJobManager
{
    public function __construct(
        private readonly ImportJobRepository $jobs,
        private readonly ImportLogRepository $logs,
    ) {
    }

    /**
     * Registers a new job in the `queued` state and logs the `queued` event.
     *
     * @param array<string, mixed> $data Must include school_id, assessment_id,
     *     file_path, file_type, uploaded_by.
     */
    public function createQueuedJob(array $data): int
    {
        $jobId = (int) $this->jobs->create([...$data, 'status' => 'queued']);

        $this->logs->create([
            'import_job_id' => $jobId,
            'event' => 'queued',
            'actor_id' => $data['uploaded_by'] ?? null,
        ]);

        return $jobId;
    }

    /**
     * Transitions queued → processing (cron/service picking up the job).
     *
     * No `import_logs` entry is written here — docs/Data-Dictionary.md §5 is
     * explicit that `event` is "one of queued, parsed, validated, mapped,
     * committed, rejected"; "processing" is not in that vocabulary. The
     * `import_jobs.status` column already records this transition; the
     * pipeline-stage events below (via log()) are the FR-008 audit trail.
     * (Found and fixed during T2.3 — this method logged an invalid
     * "processing" event when it was first written in T2.1.)
     */
    public function markProcessing(int $jobId): void
    {
        $this->jobs->update($jobId, ['status' => 'processing']);
    }

    /** Terminal, success transition. */
    public function markCommitted(int $jobId): void
    {
        $this->jobs->update($jobId, ['status' => 'committed']);
        $this->logs->create(['import_job_id' => $jobId, 'event' => 'committed']);
    }

    /**
     * Terminal, failure transition. Per FR-006, $errorDetail must be
     * specific enough to name a row/column/value — a generic message is not
     * acceptable, though enforcing that is the caller's responsibility, not
     * this method's.
     */
    public function markFailed(int $jobId, string $errorDetail): void
    {
        $this->jobs->update($jobId, ['status' => 'failed', 'error_detail' => $errorDetail]);
        $this->logs->create(['import_job_id' => $jobId, 'event' => 'rejected', 'message' => $errorDetail]);
    }

    /** A free-form audit entry not tied to a state transition (e.g. "parsed", "mapped"). */
    public function log(int $jobId, string $event, ?string $message = null, ?int $actorId = null): void
    {
        $this->logs->create([
            'import_job_id' => $jobId,
            'event' => $event,
            'message' => $message,
            'actor_id' => $actorId,
        ]);
    }

    /** @return array<int, array<string, mixed>> The full audit trail for one job, oldest first. */
    public function history(int $jobId): array
    {
        return $this->logs->findByImportJob($jobId);
    }

    /** @return array<int, array<string, mixed>> Jobs waiting for cron pickup. */
    public function queuedJobs(): array
    {
        return $this->jobs->findQueued();
    }
}
