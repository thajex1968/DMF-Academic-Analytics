# IDR-009 — Cron Import Runner: Template Resolution, Entry-Point Convention, Batch Size

**Status:** Accepted — 2026-07-03
**Implements:** [decisions/IDR-005](IDR-005-database-connection-strategy.md) (completes the
`ConnectionFactory` that IDR-005 already designed but nothing had yet needed until now); Task T2.7
([IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md#2-task)) — "the cron-polled job runner and the
commit transaction."

## Context

T2.3 already built the commit-transaction half of T2.7 (`ImportTransactionService`, wrapping
`Dmf\Core\Contract\ConnectionInterface::transaction()`). What remained was the cron-polled runner
itself, and three genuine gaps surfaced while designing it, each confirmed by reading the actual
code/docs rather than assumed:

1. **No mechanism resolves `import_jobs` row → `ImportTemplate`.** `ScoreImportService::import()`'s
   own docblock is explicit that "the caller resolves *which* template applies and passes it in —
   this service does not guess a template key from assessment metadata, since no documented
   convention establishes one." Every existing caller (all tests) hardcodes
   `ExampleTemplates::studentIdAndScore()`. A cron runner has no human caller to supply one.
2. **No CLI/cron entry-point convention exists anywhere in the DMF Platform.** Neither this project
   nor `dmf-core` nor `../grade.dmf.ac.th` (the reference implementation `CLAUDE.md` names for
   conventions) has a `bin/` directory, a shebang PHP script, or a Console/Command class hierarchy.
   `grade.dmf.ac.th`'s actual cron-invoked PHP is a plain, non-shebang `.php` file under
   `public_html/api/system/`, invoked by full path from crontab (`php ~/public_html/api/system/status.php`),
   confirmed directly from its `docs/OPERATIONS.md`/`docs/PRODUCTION.md`.
3. **`ImportJobRepository::findQueued()` returns queued jobs in no defined order** — it delegates to
   `dmf-core`'s inherited `findWhere()`, which builds `SELECT * FROM import_jobs WHERE status = ?`
   with no `ORDER BY`. If job order matters for the runner (it does — earlier uploads should process
   before later ones), this needed fixing before a runner could depend on it.

## Decision

* **Template resolution**: a new `DMF\Import\Template\TemplateResolver`, constructed with a
  `TemplateRegistry` and a single `$defaultTemplateKey` string. `resolveForAssessment(int
  $assessmentId): ImportTemplate` — v1.0 always returns the one template registered under
  `$defaultTemplateKey`, regardless of `$assessmentId`. This is confirmed with the user before
  building: no real สทศ file specification has been provided, the same gap T2.2/T2.3 already
  flagged and stopped at rather than fabricate — this class does not pretend to solve that; it
  isolates the "one template for now" decision behind a single method so a future, real
  per-academic-year registry lookup replaces only this method's body, not every call site.
* **CLI entry point**: `public_html/api/cron/import_runner.php` — a plain, non-shebang, procedural
  PHP script, matching `grade.dmf.ac.th`'s established pattern exactly (not a new `bin/` directory,
  not a Console/Command framework). `crontab` invokes it as `php
  ~/public_html/api/cron/import_runner.php`. It boots the app via `bootstrap/app.php`, constructs a
  real `Connection` via the new `ConnectionFactory`, wires the same classes the test suite already
  wires over a mocked connection, runs one batch, and writes a one-line outcome summary to STDOUT
  (captured by crontab's own `>> log 2>&1` redirection, the same mechanism `grade.dmf.ac.th` uses —
  no new logging infrastructure invented here).
* **`ConnectionFactory` is built now** — not a new decision, this is IDR-005's already-accepted
  design (`DMF\Database\ConnectionFactory::fromConfig(Config $config): Connection`), simply never
  needed until this task, since every prior task's production code received its `ConnectionInterface`
  from a test double or an as-yet-unbuilt caller. `TransactionManager` (IDR-005's other half) remains
  deferred — this pipeline is still one top-level transaction per commit
  (`ImportTransactionService`), so the nesting-guard `TransactionManager` exists to provide still has
  no real trigger; building it now would be the same speculative work IDR-005 already declined.
* **Bounded batch per run**: `ImportJobRunner::run()` processes at most `$maxJobsPerRun` (default
  10) queued jobs per invocation, oldest first (`ImportJobRepository::findQueued()`, fixed below to
  `ORDER BY created_at ASC, id ASC`). Per [docs/01-PRD.md §20](../docs/01-PRD.md#20-non-functional-requirements),
  one file's import must complete within 30 seconds; an unbounded loop over every currently-queued
  job in one cron tick has no such guarantee once several files queue up at once, and CLAUDE.md's
  hosting constraint ("no long-running workers outside cron") means each invocation must return
  promptly so the *next* scheduled tick (documented as "every 1 min" in
  [docs/02-System-Architecture.md §7](../docs/02-System-Architecture.md#7-import-pipeline-architecture)'s
  sequence diagram) picks up whatever's left, rather than one script instance running indefinitely.
* **`findQueued()` fixed to `ORDER BY created_at ASC, id ASC`** — written as its own SQL directly
  (matching `findBySchool()`'s/`findActiveJobsForSchoolAndAssessment()`'s existing pattern), no
  longer delegating to the unordered inherited `findWhere()`.
* **Per-job failure isolation**: `ImportJobRunner::run()` wraps each job's processing in its own
  `try`/`catch`; an exception outside `ScoreImportService::import()`'s own handling (e.g. template
  resolution failing) marks that one job `failed` with the same safe, generic message convention
  established in T2.3/T2.6, and the runner continues to the next queued job — one bad job can never
  abort an entire cron tick.

## Alternatives Considered

* **A `bin/` directory with a shebang CLI script** (the more common modern-PHP convention) —
  rejected. It would be the first of its kind anywhere in the DMF Platform family; matching the
  established `grade.dmf.ac.th` convention (CLAUDE.md's explicit instruction) outweighs following a
  more generic PHP-ecosystem pattern this platform has never actually used.
* **Defer T2.7 entirely until a real template specification exists** — rejected. Duplicate
  Detection, Audit Trail, and the whole Score Import Pipeline are already fully built and tested
  against the same example-template discipline (T2.2/T2.3/T2.6); withholding the runner would leave
  a real, working pipeline with no way to ever actually run outside a test. Building the runner
  around an explicitly-labeled, swappable default is consistent with how every prior task in this
  sprint already handled the same gap.
* **Runner takes a single `ImportTemplate` as a fixed constructor argument** (no resolver
  abstraction) — rejected as a false simplification: it would still need re-wiring the moment a
  second template exists, exactly the scaling problem `TemplateResolver` avoids for one extra
  constructor parameter today.
* **Unbounded batch (process every queued job in one run)** — rejected per the 30-second-per-file
  NFR and the "no long-running workers" hosting constraint; a bounded batch with the next cron tick
  picking up the remainder is the same pattern the architecture diagram's "every 1 min" polling
  interval already implies.

## Consequences

* A future PR that has a real สทศ file specification only needs to change how
  `TemplateResolver::resolveForAssessment()` looks up a key (e.g. a real per-academic-year DB-backed
  registry) — no caller of `TemplateResolver` changes.
* `public_html/api/cron/import_runner.php` is the first file under `public_html/` this project has
  ever created — the directory itself did not exist before this task.
* Operationally, deploying this runner requires one crontab line
  (`* * * * * php ~/public_html/api/cron/import_runner.php >> ~/storage/logs/import_cron.log 2>&1`,
  matching `grade.dmf.ac.th`'s redirection convention) — not part of this codebase, a deployment
  step for whoever provisions the DirectAdmin cron job.
