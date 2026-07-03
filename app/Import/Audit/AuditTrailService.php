<?php

declare(strict_types=1);

namespace DMF\Import\Audit;

use DateTimeImmutable;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\ImportLogRepository;

/**
 * FR-008 (Import Log & Audit Trail) — the Audit layer this task's
 * architecture ("Repository → Service → Audit → DTO") sits between the
 * pipeline services (DMF\Import\Score\*, DMF\Import\Session\*) and the
 * typed AuditEvent DTOs a caller (or future dashboard) consumes.
 *
 * Two responsibilities, kept distinct:
 *
 * 1. **Write** the four new event types (via ImportAuditLogger) through
 *    small, safe-by-construction convenience methods — every message is
 *    built from structured data the caller already legitimately has
 *    (counts, ids, student ids), never from a raw exception/PDO message
 *    (see this task's Security requirement).
 * 2. **Read** the full timeline for one job (timelineFor()) by composing
 *    the existing `import_logs` rows (written by both ImportJobManager and
 *    ImportAuditLogger — this class does not care which) with the parent
 *    `import_jobs.school_id`, reconstructing each row as a typed AuditEvent
 *    with a derived `status`. This is how "reuse import_logs, don't
 *    duplicate storage" and "audit must include school id/status" are both
 *    satisfied at once: school_id/status are computed at read time, never
 *    stored redundantly.
 */
final class AuditTrailService
{
    public function __construct(
        private readonly ImportAuditLogger $logger,
        private readonly ImportLogRepository $logs,
        private readonly ImportJobRepository $jobs,
    ) {
    }

    /**
     * Logged once processing genuinely begins — the gap
     * DMF\Import\ImportJobManager::markProcessing() deliberately left open
     * in T2.1/T2.3 (the vocabulary didn't support it then; decisions/IDR-008
     * added `import_started` for exactly this). System/cron-triggered, so
     * $actorId is always null, matching the same convention every other
     * system-generated event in this table already follows.
     */
    public function recordImportStarted(int $importJobId, ?int $schoolId): void
    {
        $this->logger->record(new AuditEvent(
            $importJobId,
            AuditEvent::EVENT_IMPORT_STARTED,
            AuditEvent::STATUS_INFO,
            $schoolId,
            null,
            'Import processing started.',
            [],
            new DateTimeImmutable(),
        ));
    }

    /** Logged when DuplicateDetectionService::detect() finds any of its four signals. */
    public function recordDuplicateFound(int $importJobId, ?int $schoolId, DuplicateCheckResult $result): void
    {
        $this->logger->record(new AuditEvent(
            $importJobId,
            AuditEvent::EVENT_DUPLICATE_FOUND,
            AuditEvent::STATUS_WARNING,
            $schoolId,
            null,
            $result->summary(),
            [
                'within_file_count' => count($result->withinFileDuplicates),
                'already_imported_count' => count($result->alreadyImportedDuplicates),
                'duplicate_job_id' => $result->duplicateImportJob['id'] ?? null,
                'active_duplicate_job_ids' => $result->activeDuplicateJobIds,
            ],
            new DateTimeImmutable(),
        ));
    }

    /** Logged when a failed job is re-queued for another attempt. */
    public function recordRetry(int $importJobId, ?int $schoolId, ?int $actorId = null): void
    {
        $this->logger->record(new AuditEvent(
            $importJobId,
            AuditEvent::EVENT_RETRY,
            AuditEvent::STATUS_INFO,
            $schoolId,
            $actorId,
            'Import job retry requested.',
            [],
            new DateTimeImmutable(),
        ));
    }

    /**
     * Logged when the commit transaction is rolled back (a Throwable escaped
     * ImportTransactionService::commit()'s closure) — a finer-grained
     * diagnostic signal than the `rejected` event ImportJobManager::markFailed()
     * already logs for the same failure; this event is additional, not a
     * replacement. $reason must already be a safe, generic string — never
     * the raw exception message (Security requirement: no SQLSTATE, no PDO
     * detail, no stack trace in any audited/persisted message).
     */
    public function recordRollback(
        int $importJobId,
        ?int $schoolId,
        string $reason = 'Database transaction rolled back during commit.',
    ): void {
        $this->logger->record(new AuditEvent(
            $importJobId,
            AuditEvent::EVENT_ROLLBACK,
            AuditEvent::STATUS_ERROR,
            $schoolId,
            null,
            $reason,
            [],
            new DateTimeImmutable(),
        ));
    }

    /**
     * The full, typed audit trail for one import job, oldest first —
     * reconstructs every persisted `import_logs` row (regardless of whether
     * ImportJobManager or ImportAuditLogger wrote it) into an AuditEvent,
     * joining `import_jobs.school_id` once for the whole timeline.
     *
     * @return AuditEvent[]
     */
    public function timelineFor(int $importJobId): array
    {
        $job = $this->jobs->findById($importJobId);
        $schoolId = $job !== null ? (int) $job['school_id'] : null;

        $rows = $this->logs->findByImportJob($importJobId);

        return array_map(
            static fn (array $row): AuditEvent => new AuditEvent(
                (int) $row['import_job_id'],
                (string) $row['event'],
                self::statusFor((string) $row['event']),
                $schoolId,
                $row['actor_id'] !== null ? (int) $row['actor_id'] : null,
                (string) ($row['message'] ?? ''),
                [],
                new DateTimeImmutable((string) $row['created_at']),
            ),
            $rows,
        );
    }

    /** Derives a display status from a stored event — never itself persisted as a column. */
    public static function statusFor(string $event): string
    {
        return match ($event) {
            AuditEvent::EVENT_VALIDATED, AuditEvent::EVENT_COMMITTED => AuditEvent::STATUS_SUCCESS,
            AuditEvent::EVENT_REJECTED, AuditEvent::EVENT_ROLLBACK => AuditEvent::STATUS_ERROR,
            AuditEvent::EVENT_DUPLICATE_FOUND => AuditEvent::STATUS_WARNING,
            default => AuditEvent::STATUS_INFO,
        };
    }
}
