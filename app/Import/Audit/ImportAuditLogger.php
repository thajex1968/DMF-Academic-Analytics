<?php

declare(strict_types=1);

namespace DMF\Import\Audit;

use DMF\Repository\ImportLogRepository;
use InvalidArgumentException;

/**
 * Persists the four audit events T2.6 (FR-008) added
 * (decisions/IDR-008) — `duplicate_found`, `import_started`, `retry`,
 * `rollback` — into the existing `import_logs` table via
 * DMF\Repository\ImportLogRepository, the same storage
 * DMF\Import\ImportJobManager already writes `queued`/`parsed`/`mapped`/
 * `validated`/`committed`/`rejected` into.
 *
 * Deliberately refuses to write any of those six pre-existing events:
 * ImportJobManager remains their sole writer, at its own pipeline-transition
 * points, so no occurrence is ever logged twice by two different code paths
 * — "reuse import_logs, never create redundant storage" (this task's
 * explicit instruction) enforced structurally, not just by convention.
 *
 * $event->message is trusted to already be safe (see AuditTrailService's
 * convenience methods, which build every message from structured data —
 * counts, ids, student ids the caller already has legitimate access to —
 * never from a raw exception/PDO message). This class does not attempt to
 * further sanitize it; the security boundary is "construct only safe
 * AuditEvents," enforced at the call site, not "filter unsafe ones here."
 */
final class ImportAuditLogger
{
    /** @var string[] */
    private const WRITABLE_EVENTS = [
        AuditEvent::EVENT_DUPLICATE_FOUND,
        AuditEvent::EVENT_IMPORT_STARTED,
        AuditEvent::EVENT_RETRY,
        AuditEvent::EVENT_ROLLBACK,
    ];

    public function __construct(
        private readonly ImportLogRepository $logs,
    ) {
    }

    public function record(AuditEvent $event): void
    {
        if (!in_array($event->event, self::WRITABLE_EVENTS, true)) {
            throw new InvalidArgumentException(sprintf(
                'ImportAuditLogger does not write the "%s" event — it is already logged by ' .
                    'DMF\Import\ImportJobManager at its own pipeline-transition point ' .
                    '(decisions/IDR-008).',
                $event->event,
            ));
        }

        $this->logs->create([
            'import_job_id' => $event->importJobId,
            'event' => $event->event,
            'message' => $event->message,
            'actor_id' => $event->actorId,
        ]);
    }
}
