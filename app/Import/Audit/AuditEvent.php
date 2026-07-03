<?php

declare(strict_types=1);

namespace DMF\Import\Audit;

use DateTimeImmutable;

/**
 * One entry in an import job's audit trail (FR-008) — either about to be
 * written via ImportAuditLogger, or reconstructed from a persisted
 * `import_logs` row by AuditTrailService::timelineFor(). `$event` is always
 * one of the `import_logs.event` vocabulary values (self::EVENT_* — see
 * docs/03-Database-Design.md §7 and decisions/IDR-008); `$status` is a
 * derived, application-layer classification of that event, never persisted
 * as its own column (see AuditTrailService::statusFor()).
 *
 * `$schoolId` and `$context` exist for this DTO's callers (a future
 * dashboard, this layer's own convenience methods) but are **not** persisted
 * as separate `import_logs` columns — `$schoolId` is always derivable via a
 * join to `import_jobs.school_id`, and `import_logs` has no structured
 * `context` column (see decisions/IDR-008's "Alternatives Considered" for
 * why a new column/table was rejected). `$message` must always be a safe,
 * user-presentable string — never a raw exception message, SQLSTATE code, or
 * stack trace (see ImportAuditLogger's docblock).
 */
final class AuditEvent
{
    /** Written exclusively by DMF\Import\ImportJobManager at its own pipeline-transition points. */
    public const EVENT_QUEUED = 'queued';
    public const EVENT_PARSED = 'parsed';
    public const EVENT_MAPPED = 'mapped';
    public const EVENT_VALIDATED = 'validated';
    public const EVENT_COMMITTED = 'committed';
    public const EVENT_REJECTED = 'rejected';

    /** Written exclusively by ImportAuditLogger — added by T2.6, decisions/IDR-008. */
    public const EVENT_DUPLICATE_FOUND = 'duplicate_found';
    public const EVENT_IMPORT_STARTED = 'import_started';
    public const EVENT_RETRY = 'retry';
    public const EVENT_ROLLBACK = 'rollback';

    public const STATUS_INFO = 'info';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_WARNING = 'warning';
    public const STATUS_ERROR = 'error';

    /** @param array<string, mixed> $context */
    public function __construct(
        public readonly int $importJobId,
        public readonly string $event,
        public readonly string $status,
        public readonly ?int $schoolId,
        public readonly ?int $actorId,
        public readonly string $message,
        public readonly array $context,
        public readonly DateTimeImmutable $occurredAt,
    ) {
    }
}
