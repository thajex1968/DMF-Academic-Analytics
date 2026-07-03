# IDR-008 — `import_logs.event` Vocabulary Extension for Duplicate Detection + Audit Trail

**Status:** Accepted — 2026-07-03
**Implements:** [IDR-003](IDR-003-pdo-for-database-layer.md) (no ORM, direct parameterized SQL) · Task T2.6 ([IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md#2-task)) — Duplicate Detection (FR-007) + Import Log/Audit Trail (FR-008)

## Context

T2.6 requires an audit trail that can represent, as first-class events, several actions that
[docs/01-PRD.md FR-008](../docs/01-PRD.md) implies but the existing `import_logs.event` vocabulary
(`queued`, `parsed`, `validated`, `mapped`, `committed`, `rejected` — fixed by T2.1/T2.3) cannot
express: a duplicate being detected, processing genuinely starting (as distinct from the job merely
being queued), a retry being requested, and a transaction rollback occurring during commit.

`ImportJobManager::markProcessing()` already faced this exact question once, in T2.1/T2.3: it
originally logged an invalid `'processing'` event, was found and fixed to log nothing at all,
reasoning that `import_jobs.status` already recorded the transition and the vocabulary didn't
support a corresponding log event. `RetryFailedImport` (T2.4) faced the same question for "retry"
and chose to reuse the existing `'queued'` event with a distinguishing `message` string
(`'Retry requested.'`) rather than ask for a new value.

T2.6's explicit instruction is stronger than either of those two prior calls: it asks for a
structured audit trail with typed events, including "Duplicate Found" and "Retry" by name as
required examples, and states the architecture rule "Audit → DTO" — implying event *type* is
meaningful application-layer data, not just message text. Continuing to fold every new semantic
into free-text `message` strings on top of a fixed 6-value vocabulary would make the audit trail
progressively less queryable by event type, which cuts directly against FR-008's acceptance
criterion: "every commit or rejection is queryable" — the same reasoning extends to "every
duplicate-found, retry, and rollback."

## Decision

Extend `import_logs.event` (`VARCHAR(50)`, not a DB `ENUM` — a documented, application-enforced
vocabulary, per [docs/03-Database-Design.md §7](../docs/03-Database-Design.md#7-import-pipeline)) from
6 to 10 values, adding:

* `duplicate_found` — a `DuplicateDetectionService` check found a within-file, already-imported,
  duplicate-job-path, or concurrent-active-job collision.
* `import_started` — processing genuinely began (the gap `markProcessing()` deliberately left open
  in T2.1/T2.3, now filled by the new `app/Import/Audit/AuditTrailService`, **not** by modifying
  `ImportJobManager::markProcessing()` itself — see Consequences).
* `retry` — a failed job was re-queued for another attempt.
* `rollback` — the commit transaction was rolled back (a `Throwable` was thrown inside
  `ImportTransactionService::commit()`'s closure); logged *in addition to*, not instead of, the
  existing `rejected` event `ImportJobManager::markFailed()` already writes for the same failure —
  `rollback` is a finer-grained diagnostic signal, `rejected` remains the terminal, job-level
  outcome every existing caller already expects.

`docs/03-Database-Design.md` §7 and `docs/Data-Dictionary.md` §5 are amended in this same change
(Post-Freeze Amendment, version bumps + revision history rows in each, plus
[docs/00-Project-Overview.md §13](../docs/00-Project-Overview.md#13-documentation-freeze)'s log) —
per [IMPLEMENTATION_GUIDE.md §6](../IMPLEMENTATION_GUIDE.md#6-definition-of-done): "every
new/changed... column matches Naming-Convention.md and has a corresponding entry in
Data-Dictionary.md — added in the same pull request."

**Ownership split** (who is allowed to write which event, enforced in code by
`ImportAuditLogger::WRITABLE_EVENTS`): the six pre-existing values remain written exclusively by
`ImportJobManager` at its own pipeline-transition points, unchanged. The four new values are
written exclusively by the new `app/Import/Audit/ImportAuditLogger`. Neither class writes an event
the other owns — this keeps "Reuse existing `import_logs`, never create redundant storage" (the
task's explicit instruction) true in the strongest sense: no event is ever logged twice by two
different code paths for the same occurrence.

## Alternatives Considered

* **Reuse existing values with a distinguishing `message` string** (the `RetryFailedImport`
  precedent) for all four new semantics too. Rejected: defeats "queryable by event type," which is
  exactly what a `SELECT ... WHERE event = 'duplicate_found'` style query needs and a `LIKE
  '%duplicate%'` scan over `message` text does not reliably support; also does not match this task's
  explicit request for a typed `AuditEvent` DTO with a `status`/`event` distinction.
* **A new, separate `import_audit_events` table** (richer columns: `status`, `school_id`,
  structured `context` as `JSON`). Rejected: directly contradicts the task's explicit instruction —
  "Reuse existing `import_logs` whenever possible. Do not create redundant storage." — and there is
  no concrete consumer yet that needs to query audit events independently of their parent
  `import_job_id` (YAGNI, [Architecture-Principles.md §7](../docs/Architecture-Principles.md#7-yagni--you-arent-gonna-need-it)).
  `school_id`/`status`/structured `context` are instead derived at read time by
  `AuditTrailService::timelineFor()` (school_id via a join to `import_jobs`, status via a pure
  `event → status` mapping function) rather than stored redundantly.
* **Change `import_logs.event` to a real MySQL `ENUM` column** now that the vocabulary is more than
  incidental. Rejected: out of scope for this IDR — the column is already `VARCHAR(50)`, and
  converting it is an unrelated migration-and-column-type decision with its own tradeoffs (an
  `ENUM` alter is not free-standing per this project's forward-only migration convention,
  [docs/03-Database-Design.md §16](../docs/03-Database-Design.md#16-migration-strategy)); nothing in
  T2.6 requires it.
* **Log `import_started` by modifying `ImportJobManager::markProcessing()` directly** (reversing
  the T2.1/T2.3 decision now that the vocabulary supports it). Considered, but rejected in favor of
  logging it from the new `AuditTrailService` call site inside `ScoreImportService::import()`
  instead — avoids re-touching already-approved, already-tested `ImportJobManager` code for a
  capability the new Audit layer can provide on its own, and keeps `ImportJobManager`'s existing
  test suite (T2.1) untouched.

## Consequences

* `ImportLogRepository`/`ImportJobManager` are unchanged — both continue to write exactly the six
  events they always have; no modification to already-approved T2.1/T2.3 code.
* `ScoreImportService` (T2.3) and `RetryFailedImport` (T2.4) each gain one new constructor
  dependency (`DuplicateDetectionService`/`AuditTrailService` and `AuditTrailService`, respectively)
  and their existing test suites' exact `import_logs` sequence assertions require updating to
  include the new events — expected, tracked as part of T2.6's own Definition of Done, not
  incidental breakage.
* A future PR that wants to query "every duplicate found this month across all schools" can do so
  with a single `WHERE event = 'duplicate_found'` query against `import_logs`, joined to
  `import_jobs` for `school_id` — no new table, no new join path beyond what already exists.
