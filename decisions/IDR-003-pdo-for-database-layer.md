# IDR-003 — PDO via `dmf-core`, No ORM

**Status:** Accepted — 2026-07-02
**Implements:** [ADR-003](../docs/Architecture-Decision-Record.md#adr-003--why-mysqlmariadb) (the
architecture-level choice of MySQL/MariaDB); Task T1.2
([IMPLEMENTATION_GUIDE.md §2](../IMPLEMENTATION_GUIDE.md#2-task)).

## Context

[ADR-003](../docs/Architecture-Decision-Record.md#adr-003--why-mysqlmariadb) already decided the
database engine. It did not decide the data-access pattern every one of this module's roughly
twenty-seven tables ([docs/03-Database-Design.md](../docs/03-Database-Design.md)) will be read and
written through. `dmf-core` already ships `Database\Connection` (a lazy PDO wrapper with a
`transaction()` helper) and `Database\Repository\AbstractRepository`
(`dmf-core/docs/modules.md`) — the question this decision answers is whether this module builds on
top of that as-is, or adds another data-access layer above or instead of it.

## Decision

Use `dmf-core`'s `Database\Connection` (PDO-based) directly, with **no ORM** layered on top. Every
DLAP domain repository (`DMF\Repository\*`) extends
`Dmf\Core\Database\Repository\AbstractRepository` and writes parameterized SQL (directly, or via
`dmf-core`'s `Database\QueryBuilder` for straightforward filters) — never raw string-interpolated
queries, and never an ORM-generated query the developer doesn't directly control.

## Alternatives Considered

* **An ORM (Doctrine, Eloquent, or similar)** — rejected. Would require a metadata-mapping layer
  (entity classes, migrations tooling, a unit-of-work/change-tracking model) disproportionate to
  this module's schema size, and would duplicate what `dmf-core`'s `Connection` +
  `AbstractRepository` already provides — a direct violation of Shared Components
  ([docs/Architecture-Principles.md
  §4](../docs/Architecture-Principles.md#4-shared-components)): `dmf-core` exists specifically so
  every DMF Platform module does not each pick its own data-access story. It would also be the
  first DMF Platform module to diverge from `grade.dmf.ac.th`'s established pattern for no
  documented reason.
* **`mysqli` instead of PDO** — rejected. `dmf-core`'s `Connection` and every `Contract\*` type
  signature already commit to PDO; switching drivers at the DLAP module level would mean either
  bypassing `dmf-core`'s Database layer entirely (also rejected, same reasoning as the ORM case) or
  maintaining a second, incompatible connection object alongside it for no benefit — `mysqli` and
  PDO offer no meaningfully different capability for this project's query patterns.
* **A query builder without `dmf-core`'s `AbstractRepository`** (e.g., each module hand-rolling its
  own thin data-access class) — rejected. `AbstractRepository` already provides `find`/`count` and
  the CRUD shape every repository needs in common
  ([docs/02-System-Architecture.md §4](../docs/02-System-Architecture.md#4-layered-architecture));
  reimplementing that per-repository would be a DRY violation
  ([docs/Architecture-Principles.md §5](../docs/Architecture-Principles.md#5-dry--dont-repeat-yourself))
  for no corresponding gain in expressiveness.

## Consequences

Every repository in this module looks structurally like `grade.dmf.ac.th`'s equivalent classes —
new team members already familiar with that reference implementation need no new mental model for
data access. The cost is what any non-ORM approach costs: no automatic lazy-loading or
change-tracking, so a repository method that needs related data (e.g., a classroom's current
teacher) fetches it explicitly, via an explicit join or a second query — an acceptable, KISS-aligned
trade ([docs/Architecture-Principles.md §6](../docs/Architecture-Principles.md#6-kiss--keep-it-simple))
given this module's query patterns are already known and finite (§17 of
[docs/03-Database-Design.md](../docs/03-Database-Design.md) lists the representative ones), not
open-ended enough to need an ORM's flexibility.
