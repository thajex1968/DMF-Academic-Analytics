# IDR-007 — Idempotent Raw-SQL Seed Files, Mirroring the Migration Pattern

**Status:** Accepted — 2026-07-03
**Implements:** [IDR-003](IDR-003-pdo-for-database-layer.md) (PDO via `dmf-core`, No ORM);
[IMPLEMENTATION_GUIDE.md §2](../IMPLEMENTATION_GUIDE.md#2-task) Task T1.4.

## Context

`Naming-Convention.md` and `03-Database-Design.md` specify a format for migration files
(`YYYYMMDD_HHMMSS_description.sql`) but say nothing about seed files — no convention, no
example, no directory even mentioned in `CLAUDE.md`'s "Planned Repository Structure" (which
lists `database/ # schema.sql, migrations/` and omits `seeders/`). T1.4 needs an answer before
any seed file can be written.

The natural alternative — a `SeederInterface`/`Seeder` PHP class hierarchy, run by a
`SeederEngine` — is exactly the shape `dmf-core`'s sibling project (`core.dmf.ac.th`) used, but
no such code exists in this project yet, and neither does the `MigrationEngine`/`Connection`
wiring (`decisions/IDR-005-database-connection-strategy.md` explicitly defers building the
Migration Engine to "Module 2, not yet built"). Building a `SeederInterface` class hierarchy now,
with nothing yet able to run it as PHP, would be scaffolding ahead of its own consumer.

## Decision

Seed files are raw, idempotent `.sql` files under `database/seeders/`, one file per data group,
numbered `NNN_description.sql` (a plain sequential counter, not a timestamp — unlike migrations,
a seed file is re-runnable and not a historical record of "when this changed," so ordering is all
a filename needs to convey). Idempotency is achieved with `INSERT ... ON DUPLICATE KEY UPDATE`
keyed on each table's real unique constraint (`assessment_types.code`, `subjects.subject_code`),
so re-running a seed file is always safe and produces the same end state, never a duplicate-row
error.

Like migrations, these files are executed directly (`mysql ... < file.sql`, or later, the same
future runner that executes migrations) — no `SeederInterface`/`SeederEngine` PHP class exists
yet, matching migrations' own current state exactly. When a real Migration/Seeder Engine is
built (Module 2), it can run these same files unchanged; this decision does not need revisiting
at that point, only the runner's existence changes.

## Alternatives Considered

**A `SeederInterface implements run(PDO $connection): void` class hierarchy**, matching
`core.dmf.ac.th`'s pattern. Rejected for now — no `MigrationEngine`/`SeederEngine` exists in this
project to run such a class, and building one now, before it has a real caller, is exactly the
premature-abstraction YAGNI (`Architecture-Principles.md §7`) warns against. Revisit if/when a
real Migration Engine is built and needs a typed contract to run seed logic through.

**Plain `INSERT` without `ON DUPLICATE KEY UPDATE`.** Rejected — re-running a plain `INSERT`
against already-seeded master data would fail on the unique constraint, making the seed files
unsafe to re-run during iterative local development (e.g., after a schema reset). Idempotency is
cheap here (a handful of small reference tables) and removes an entire class of "did I already
run this" developer error.

## Consequences

Seed files stay structurally identical to migration files (plain SQL, no new tooling, no new
PHP class), consistent with `IDR-003`'s "no ORM, use what's simplest for this scale" reasoning.
The cost: nothing yet enforces "seed files must be idempotent" except code review — there is no
compiler/interface contract for it, since there is no interface. Acceptable at this project's
current size (2 seed files, 5 rows total).
