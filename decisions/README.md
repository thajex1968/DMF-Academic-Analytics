# Implementation Decision Records (IDR)

**DMF Learning Analytics Platform (DLAP)**

This folder holds **Implementation Decision Records (IDR)** — a record separate from, and a level
below, the **Architecture Decision Records (ADR)** in
[docs/Architecture-Decision-Record.md](../docs/Architecture-Decision-Record.md). If you are about
to write a new decision record and are unsure which kind it is, read [§1](#1-adr-vs-idr) first.

There is also a tier *above* both: [docs/rfcs/](../docs/rfcs/README.md) holds **Requests For
Change (RFC)**, which propose changing product *scope* (e.g., "activate NT as a supported
assessment type") rather than architecture or implementation — see
[docs/rfcs/README.md §1](../docs/rfcs/README.md#1-three-tiers-rfc--adr--idr) for the full
three-tier picture (RFC → ADR → IDR). An approved RFC is often what *creates* the need for a new
ADR or IDR in the first place.

## 1. ADR vs. IDR

| | ADR | IDR |
|---|---|---|
| **Answers** | "Why this *category* of technology or pattern at all?" | "Given the ADR already chose the category, exactly how — which package, which version, which integration pattern — do we build it?" |
| **Made** | Before implementation, as part of the architecture baseline | During implementation, as each concrete choice comes up |
| **Changes** | Rarely — changing one usually means re-architecting | Routinely — a new IDR every time a new library/pattern choice is made |
| **Lives in** | [docs/Architecture-Decision-Record.md](../docs/Architecture-Decision-Record.md) (the six foundational ones, consolidated); future architecture-level decisions as individual files under `docs/adr/` | `decisions/IDR-NNN-slug.md`, one file per decision, from the start |
| **Part of the frozen baseline?** | Yes — see [docs/00-Project-Overview.md §13](../docs/00-Project-Overview.md#13-documentation-freeze) | **No** — this folder is a living record that grows throughout implementation; it is explicitly excluded from the frozen `docs/` baseline manifest for that reason |
| **Numbered** | `ONET-DOC-004`'s ADR-001–ADR-006 | `IDR-001`, `IDR-002`, ... — its own sequence, not part of the `ONET-DOC-` sequence |

**Worked example of the difference:** [ADR-005](../docs/Architecture-Decision-Record.md#adr-005--why-chartjs)
answers "why Chart.js instead of D3.js or server-side chart rendering" — an architecture-level
choice made before a single Dashboard screen existed. [IDR-002](IDR-002-chartjs-for-dashboard.md)
answers "given we're using Chart.js, do we load it from a CDN or vendor it, where does the chart
config JSON get assembled, which plugin handles the heatmap" — questions that only have answers
once someone is actually building the Dashboard module. Recording IDR-002 does not re-litigate
ADR-005; it assumes ADR-005's answer and adds the next layer of specificity.

**A decision might turn out to need both.** If, while implementing something, the right answer
turns out to contradict an existing ADR (not just add detail beneath it), that is an architecture
change — write a new architecture-level decision under `docs/adr/` instead, and update the ADR it
supersedes; do not paper over an architecture-level disagreement with an IDR.

## 2. Format

Every IDR follows the same five-part structure as an ADR entry in
[docs/Architecture-Decision-Record.md](../docs/Architecture-Decision-Record.md), scoped to a single
implementation-level decision:

```markdown
# IDR-NNN — <the concrete question this decision answers>

**Status:** Accepted — YYYY-MM-DD
**Implements:** <the ADR this sits underneath, if any> · <the module/task from IMPLEMENTATION_GUIDE.md §2 this unblocks>

## Context
## Decision
## Alternatives Considered
## Consequences
```

## 3. Naming

`decisions/IDR-NNN-kebab-case-slug.md` — zero-padded 3-digit number, assigned sequentially, never
reused; the slug is a short, kebab-case restatement of the decision's title. See
[docs/Naming-Convention.md §4](../docs/Naming-Convention.md#4-file--directory-naming).

## 4. Index

| ID | Title | Implements |
|---|---|---|
| [IDR-001](IDR-001-phpspreadsheet-for-excel-import.md) | PhpSpreadsheet for Excel Import | Task T2.1 ([IMPLEMENTATION_GUIDE.md §2](../IMPLEMENTATION_GUIDE.md#2-task)) |
| [IDR-002](IDR-002-chartjs-for-dashboard.md) | Chart.js Integration for the Dashboard Module | [ADR-005](../docs/Architecture-Decision-Record.md#adr-005--why-chartjs); Task T3.3 |
| [IDR-003](IDR-003-pdo-for-database-layer.md) | PDO via `dmf-core`, No ORM | [ADR-003](../docs/Architecture-Decision-Record.md#adr-003--why-mysqlmariadb); Task T1.2 |
| [IDR-004](IDR-004-custom-env-loader.md) | Custom, Dependency-Free .env Loader | Task T1.1/T1.2 — first implemented decision, Sprint 1 Module 1 (Core Configuration) |
| [IDR-005](IDR-005-database-connection-strategy.md) | Database Connection Strategy | [IDR-003](IDR-003-pdo-for-database-layer.md); Task T1.2/Module 2 |
| [IDR-006](IDR-006-dlap-env-prefix.md) | Module-Specific Environment Variable Prefix: `DLAP_`, Not `ONET_` | Reverses part of `docs/Naming-Convention.md §5`; made during Module 2 planning |

Add a row here in the same pull request that adds the IDR file — this index is the one place a
reader can see every implementation-level decision made so far without opening each file.

## 5. Cross-References

* Architecture-level decisions this folder deliberately does not duplicate:
  [docs/Architecture-Decision-Record.md](../docs/Architecture-Decision-Record.md).
* Where each IDR fits into the build order: [IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md).
* The engineering principles behind keeping this record at all (SSOT, Backward Compatibility):
  [docs/Architecture-Principles.md](../docs/Architecture-Principles.md).
