# IDR-005 — Database Connection Strategy

**Status:** Accepted — 2026-07-03
**Implements:** [IDR-003](IDR-003-pdo-for-database-layer.md) (PDO via `dmf-core`, No ORM);
[IMPLEMENTATION_GUIDE.md §2](../IMPLEMENTATION_GUIDE.md#2-task) Task T1.2/Module 2.

## Context

IDR-003 already decided *that* this project uses `dmf-core`'s `Database\Connection` directly. It
did not decide *how* a `Connection` gets constructed and shared across a request, nor how
transactions and multi-statement schema migrations are handled safely. Reading `dmf-core`'s actual
source (not assumed) confirms:

* `Connection::__construct(string $host, string $database, string $username, string $password, int
  $port = 3306, array $options = [])` — a positional constructor, matching `config/database.php`'s
  array shape field-for-field.
* `Connection::pdo(): PDO` opens the connection **lazily**, on first use — already exactly the
  behavior this project wants; nothing needs to wrap it to add laziness.
* `Connection::transaction(callable $callback): mixed` begins, commits, and rolls back on any
  `Throwable` — but PDO itself throws on a **nested** `beginTransaction()` call, and `dmf-core`'s
  implementation does not guard against that; a callback that (directly or through a repository
  method) triggers a second `transaction()` call produces a raw, unclear `PDOException`, not a
  domain-meaningful error.
* MySQL DDL statements (`CREATE TABLE`, etc.) cause an **implicit commit** — wrapping a multi-table
  schema migration in `Connection::transaction()` would not make it atomic; each statement commits
  as it runs regardless. This is a genuine MySQL limitation, not a `dmf-core` gap, and must be
  designed around, not papered over.

## Decision

* **`DMF\Database\ConnectionFactory::fromConfig(Config $config): Connection`** — a single static
  factory method that reads the `'database'` config group (per `config/database.php`'s shape) and
  constructs one `Dmf\Core\Database\Connection`. Called once per process (once per CLI script
  invocation; once per HTTP request once the front controller exists — a later module). No global
  registry, no singleton pattern — the constructed `Connection` is passed explicitly to whatever
  needs it (repositories, the migration runner), matching this project's "no Service Container
  yet" state (that is a separate, later Module 2+ task, not assumed here).
* **`DMF\Database\TransactionManager`** — a thin wrapper around `Connection::transaction()` that
  tracks nesting depth and throws a clear `Dmf\Core\Exception\DatabaseException` (reusing the
  existing exception, not inventing a new one) if a transaction is attempted while one is already
  open, instead of letting a confusing low-level `PDOException` surface. This is the one place a
  wrapper adds real value over calling `Connection::transaction()` directly — everywhere else,
  application code calls `Connection::transaction()` itself, not through this class.
* **Migrations are not wrapped in a database transaction.** The Migration Engine (Module 2) runs
  each migration file's statements directly via `PDO::exec()` and records success in
  `schema_migrations` immediately after — because DDL auto-commits regardless, claiming
  transactional safety here would be dishonest. If a migration file's later statement fails after
  an earlier one in the same file already committed, the fix is a new migration that corrects the
  partial state, the same "no partial commits get silently retried, a new pass fixes it" principle
  [Business-Flow.md §5](../docs/Business-Flow.md#5-storage) already uses for import commits.

## Alternatives Considered

* **A global `Connection` singleton/registry (e.g., a static `DB::connection()` facade)** —
  rejected. It is a common pattern in full frameworks (Laravel's `DB` facade), which this project
  is explicitly instructed not to build toward; it also hides the dependency instead of making it
  an explicit constructor parameter, working against testability (a repository test can inject a
  fake `ConnectionInterface` only if the dependency is explicit).
* **Wrap every migration file's statements in `Connection::transaction()` anyway, "for safety"** —
  rejected as actively misleading: it would look like it provides atomicity it cannot actually
  provide for DDL, which is worse than no wrapping at all — a false sense of safety is a defect,
  not a mitigation.
* **Support nested transactions via SQL `SAVEPOINT`** — rejected for now. No current task needs it
  (the import commit pipeline is one top-level transaction per commit, per
  [03-Database-Design.md §13](../docs/03-Database-Design.md#13-data-integrity-rules)); `SAVEPOINT`
  support can be added to `TransactionManager` later, behind its own IDR, if a real nested-write
  need appears — building it now would be speculative, per
  [Architecture-Principles.md §7](../docs/Architecture-Principles.md#7-yagni--you-arent-gonna-need-it)
  (YAGNI).

## Consequences

`ConnectionFactory` and `TransactionManager` are both small, fully unit-testable without a real
database connection (construction/guard logic only — no network I/O happens until `Connection::
pdo()` is actually called). The Migration Engine's "no transactional DDL" limitation is documented
here once and referenced from the engine's own class-level doc comment, rather than being a
surprise someone discovers by reading MySQL's manual mid-incident.
