# Architecture Principles

**DMF Learning Analytics Platform (DLAP)**

| | |
|---|---|
| **Document ID** | ONET-DOC-005 |
| **Version** | 1.0.1 |
| **Status** | Frozen — DLAP Documentation Baseline v2.0.0 |
| **Date** | 2026-07-02 |
| **Author** | DMF Platform Team |
| **Related documents** | [00-Project-Overview](00-Project-Overview.md) · [01-PRD](01-PRD.md) · [02-System-Architecture](02-System-Architecture.md) · [03-Database-Design](03-Database-Design.md) · [Architecture-Decision-Record](Architecture-Decision-Record.md) |

## Revision History

| Version | Date | Description | Author |
|---|---|---|---|
| 1.0.0 | 2026-07-02 | Initial release. Consolidates the eight cross-cutting engineering principles this document set and all future implementation must follow, so they are defined once instead of restated in every document. | DMF Platform Team |
| 1.0.1 | 2026-07-02 | QA fix (see [Documentation-QA-Report.md](Documentation-QA-Report.md)): corrected two relative links to `CLAUDE.md` that omitted the `../` needed from within `docs/`. Frozen as part of the DLAP Documentation Baseline v2.0.0 ([00-Project-Overview.md §13](00-Project-Overview.md#13-documentation-freeze)). | DMF Platform Team |

## Table of Contents

1. [Single Source of Truth (SSOT)](#1-single-source-of-truth-ssot)
2. [Convention over Configuration](#2-convention-over-configuration)
3. [Module Isolation](#3-module-isolation)
4. [Shared Components](#4-shared-components)
5. [DRY — Don't Repeat Yourself](#5-dry--dont-repeat-yourself)
6. [KISS — Keep It Simple](#6-kiss--keep-it-simple)
7. [YAGNI — You Aren't Gonna Need It](#7-yagni--you-arent-gonna-need-it)
8. [Backward Compatibility](#8-backward-compatibility)
9. [How These Principles Interact](#9-how-these-principles-interact)
10. [Cross-References](#10-cross-references)

---

## Purpose

Every document in this set — [01-PRD.md](01-PRD.md), [02-System-Architecture.md](02-System-Architecture.md),
[03-Database-Design.md](03-Database-Design.md), and the code that eventually implements them —
makes dozens of small design calls. Without a shared, written standard for *how* to decide, those
calls drift: one module invents its own caching convention, another duplicates a validation rule
that already exists in `dmf-core`, a third builds a configurable option nothing will ever
configure. This document is that shared standard. It does not repeat architecture decisions
already recorded in [Architecture-Decision-Record.md](Architecture-Decision-Record.md) — it
defines the *principles* those decisions, and every future one, are checked against.

---

## 1. Single Source of Truth (SSOT)

**Rule:** Every fact has exactly one authoritative location. Every other place that needs the fact
references it — it does not restate it.

**Why:** This document set itself was rewritten once already (v1.1.0 → v2.0.0) because an earlier
draft ([archive/01-PRD-legacy.md](archive/01-PRD-legacy.md)) had drifted out of sync with the
actual technology direction. Restated facts rot; referenced facts don't.

**How to apply:**
* In documentation: a fact is written once, in the document that owns it, and every other document
  links to it rather than copying it. Example: the shared glossary lives only in
  [00-Project-Overview.md §11](00-Project-Overview.md#11-glossary); [01-PRD.md](01-PRD.md) links to
  it instead of maintaining a second glossary.
* In the database: `standard_performance_summary` and `student_standard_mastery`
  ([03-Database-Design.md §9](03-Database-Design.md#9-table-definitions--aggregation--materialized-summaries))
  are the single source of truth for *aggregate* performance — a dashboard never recomputes a
  percentage from raw responses when a summary row already holds it.
* In code (once it exists): a business rule (e.g., "a question has exactly one primary indicator")
  is enforced in one place — a `NOT NULL` foreign key at the database level
  ([03-Database-Design.md §13](03-Database-Design.md#13-data-integrity-rules)) — not re-validated
  independently in three different Action handlers.
* In configuration: secrets and environment-specific values live in environment variables read
  through `Dmf\Core\Config\Config`, never hardcoded in a second location "just for local dev."

## 2. Convention over Configuration

**Rule:** Where a sensible default exists, use it and make it the only option, instead of adding a
setting for something almost nobody will change.

**Why:** Every configurable option is a code path that must be built, tested, and documented for
all its possible values — most of which will never be exercised in a single-school deployment.

**How to apply:**
* Table, column, and file naming follow one fixed convention
  ([Naming-Convention.md](Naming-Convention.md)) — there is no per-module override.
* The REST API always dispatches on `"METHOD:action"` ([02-System-Architecture.md
  §6](02-System-Architecture.md#6-request-lifecycle)); it is not configurable per endpoint.
* Import processing always runs via cron, never inline on the request
  ([02-System-Architecture.md §7](02-System-Architecture.md#7-import-pipeline-architecture)) — this
  is a fixed architectural decision, not a per-deployment toggle.
* Where a real choice does exist (e.g., which assessment types are active), it is configuration
  *data* — rows in `assessment_types` ([03-Database-Design.md
  §4](03-Database-Design.md#4-table-definitions--assessment-framework)) — not a code branch, per
  §2's sibling principle in [01-PRD.md §21](01-PRD.md#21-core-product-capabilities) (reference data
  is data, not code).

## 3. Module Isolation

**Rule:** A module owns its own tables and internal logic. Other modules interact with it only
through its public interface (a repository's public methods, a service class), never by querying
its tables directly or reaching into its internals.

**Why:** This is what makes the Modular Monolith
([Architecture-Decision-Record.md, ADR-001](Architecture-Decision-Record.md#adr-001--why-modular-monolith))
actually modular rather than a single undifferentiated codebase that merely *looks* organized into
folders. It is also why a single wide `student_history` table was rejected in
[ADR-006](Architecture-Decision-Record.md#adr-006--why-a-generic-student-centric-assessment-schema) —
collapsing enrollment, scores, and mastery into one table would mean every module touching any of
that data touches all of it.

**How to apply:**
* The **Student & Enrollment** module is the only module that queries `students` and
  `student_enrollments` directly; `Import` and `Analytics` call its public methods instead
  ([02-System-Architecture.md §3](02-System-Architecture.md#3-module-decomposition)).
* Enforced by code review and PHPStan rules restricting cross-module class references to public
  service classes only ([02-System-Architecture.md §3](02-System-Architecture.md#3-module-decomposition))
  — there is no network boundary to enforce it automatically, since this is a monolith, not
  microservices.
* A migration that changes one module's table shape should never require changing another
  module's code, only its dependency's public interface if that interface's contract changed.

## 4. Shared Components

**Rule:** If two or more modules need the same capability, it is built once — in `dmf-core` if the
capability is platform-wide, or in a shared internal class if it is specific to this module — and
both modules depend on it. It is never copy-pasted.

**Why:** `dmf-core` exists specifically so that `grade.dmf.ac.th`, this module, and every future
`*.dmf.ac.th` portal do not each reimplement authentication, database access, or validation
(`dmf-core/docs/modules.md`). The same logic applies one level down, inside this module.

**How to apply:**
* Auth, database access, HTTP, validation, security, config, and logging are **always** consumed
  from `dmf-core`, never reimplemented — see
  [01-PRD.md, Conventions](../CLAUDE.md#conventions-to-follow-once-code-exists).
* Every domain repository extends `Dmf\Core\Database\Repository\AbstractRepository` for common
  CRUD ([02-System-Architecture.md §4](02-System-Architecture.md#4-layered-architecture)) instead
  of each module writing its own `find`/`create`/`update` boilerplate.
* The PDF and Excel export paths (FR-016, FR-017) both read from the same pre-aggregated summary
  tables ([02-System-Architecture.md §12](02-System-Architecture.md#12-reporting--export-architecture))
  rather than each format having its own aggregation logic.

## 5. DRY — Don't Repeat Yourself

**Rule:** Every piece of knowledge — a business rule, a calculation, a mapping — has one
authoritative implementation. This is SSOT (§1) applied specifically to logic and code, as opposed
to facts and documentation.

**Why:** Two copies of the same rule always drift eventually — one gets fixed, the other doesn't.

**How to apply:**
* Classical Test Theory statistics (FR-012) are computed once, by the Analytics module, and read
  by both the dashboard and the PDF export — never recalculated independently in each consumer.
* The Buddhist-Era/Gregorian date conversion is `dmf-core`'s `Support\ThaiDate`
  (`dmf-core/docs/modules.md`), used everywhere an academic year needs converting — this module
  does not write its own version.
* This principle has a deliberate limit: three similar-looking lines of code are not automatically
  a duplication problem. See KISS (§6) and YAGNI (§7) — do not build an abstraction to avoid three
  lines of repetition; that trade often makes the code harder to read, not easier.

## 6. KISS — Keep It Simple

**Rule:** Prefer the simplest design that correctly satisfies the requirement in front of you.
Complexity must be justified by a concrete, current need — not by how the requirement might
theoretically evolve.

**Why:** This is the same reasoning behind rejecting microservices
([ADR-001](Architecture-Decision-Record.md#adr-001--why-modular-monolith)): a distributed
architecture is objectively more capable in the abstract, but every unit of that capability the
project does not need is a unit of complexity someone still has to operate and debug.

**How to apply:**
* Import processing is a cron-polled status table, not a message queue with acknowledgements and
  dead-letter handling ([02-System-Architecture.md §7](02-System-Architecture.md#7-import-pipeline-architecture))
  — the simplest mechanism that meets the "under 30 seconds, no request timeout" requirement.
* The AI Diagnostics module's default path is a rule-based threshold lookup (FR-014); the LLM
  narrative (FR-015) is additive, not a replacement, precisely so the simple path is never blocked
  by the complex one ([02-System-Architecture.md §11](02-System-Architecture.md#11-ai-diagnostics-integration)).
* Prefer a straightforward `SELECT` against a pre-aggregated summary table over a clever
  runtime-computed query, even when the clever query would also work — see §8 of
  [02-System-Architecture.md](02-System-Architecture.md#8-analytics--aggregation-architecture).

## 7. YAGNI — You Aren't Gonna Need It

**Rule:** Do not build a feature, an abstraction, or a data-population routine before something in
the current scope actually consumes it. Being *ready* to add a capability (a reserved column, an
empty reference table) is different from *building* it, and only the latter is subject to this
rule.

**Why:** The clearest example in this document set is deliberate: [03-Database-Design.md
§9](03-Database-Design.md#9-table-definitions--aggregation--materialized-summaries)'s
`student_standard_mastery` table exists in the schema (an ADR-006-justified exception — see §9
below) but v1.0's Analytics module does **not** write to it, because no v1.0 dashboard reads it
yet. Populating it now would mean shipping code with no consumer, which is exactly what YAGNI
warns against — the schema being *ready* does not mean the *feature* should be built early.

**How to apply:**
* Word/PowerPoint export was evaluated and explicitly deferred, not built "since we might need it"
  ([01-PRD.md §7](01-PRD.md#7-out-of-scope)).
* The ten reserved `assessment_types` codes beyond `ONET` are rows a future release can insert —
  no import parser, validation rule, or dashboard view is written for them in v1.0
  ([01-PRD.md §7](01-PRD.md#7-out-of-scope)).
* The `inspector` role exists in the `Authorization\Role` hierarchy
  ([02-System-Architecture.md §9](02-System-Architecture.md#9-authentication--authorization)) as a
  reserved enum value, but no screen, policy, or workflow is built for it until the multi-school
  phase.
* **When YAGNI and future-proofing conflict:** this is the deliberate tension this whole redesign
  sits inside. The resolution used throughout this document set is: schema and naming generality
  is allowed *before* it's needed, because retrofitting a schema onto live data is expensive
  ([03-Database-Design.md §16](03-Database-Design.md#16-migration-strategy)); *feature* and
  *behavior* generality is not — a feature is built when a requirement needs it, never earlier. See
  [ADR-006](Architecture-Decision-Record.md#adr-006--why-a-generic-student-centric-assessment-schema)'s
  Consequences section for the full reasoning.

## 8. Backward Compatibility

**Rule:** Once a name, an identifier, or a public interface is established and referenced
elsewhere, changing it has a cost that must be weighed against the benefit — and often, keeping the
old name while changing the underlying meaning is cheaper and clearer than renaming everything to
match.

**Why:** This document set has already applied this rule to itself twice: the `dmf_academic`
database name, the `onet.dmf.ac.th` domain, and the `ONET-DOC-` document ID prefix all predate the
DLAP product rename and were **deliberately kept unchanged** rather than renamed a second time —
see the naming note in [00-Project-Overview.md](00-Project-Overview.md) and
[CLAUDE.md](../CLAUDE.md). Each additional rename has a real cost (every cross-reference, every
diagram label, every mental model someone has already formed) that must earn its way past this
principle, not be applied reflexively.

**How to apply:**
* Product/brand names may change (as happened here); stable technical identifiers — database
  names, domains, config key prefixes, document ID prefixes — should not, unless there is a
  concrete reason beyond "it would read more consistently."
* When a table or column is renamed for a real structural reason (as `exam_id` → `assessment_id`
  was, in [03-Database-Design.md §16](03-Database-Design.md#16-migration-strategy)), the
  Revision History records the old name so anyone holding a reference to it (a saved query, a
  memory of a prior version) can find the mapping.
* Once implementation exists, this principle governs the REST API specifically: a published
  `"METHOD:action"` contract is not changed or removed without a version bump
  ([02-System-Architecture.md §10](02-System-Architecture.md#10-integration-architecture)).

## 9. How These Principles Interact

These eight principles are not independent — several pull in opposite directions on purpose, and
the tension is the point:

* **DRY (§5) vs. KISS (§6):** DRY says don't repeat a rule; KISS says don't build an abstraction
  you don't need yet. Three similar lines are not automatically a DRY violation — see §5's own
  stated limit.
* **YAGNI (§7) vs. this entire document set's core decision (ADR-006):** the generic,
  student-centric schema is itself a bet that some future need (NT, RT, LAS) is real enough to
  prepare for structurally, ahead of YAGNI's usual "wait until it's needed." §7 explains exactly
  where that line is drawn: schema/naming generality is prepared early; feature behavior is not.
* **Convention over Configuration (§2) vs. Shared Components (§4):** a shared component still
  needs *some* configuration surface (e.g., which `assessment_type` a table row belongs to) — §2
  does not mean zero configuration, it means configuration lives in data, not in code branches.

When two principles conflict on a specific decision, the resolution is recorded as an ADR
([Architecture-Decision-Record.md](Architecture-Decision-Record.md)), not silently decided one way
inside a single document.

## 10. Cross-References

* Architecture decisions these principles were applied to reach: [Architecture-Decision-Record.md](Architecture-Decision-Record.md).
* Naming conventions these principles motivate: [Naming-Convention.md](Naming-Convention.md).
* The document set's own governance (versioning, revision history, cross-referencing) is itself an
  application of §1 (SSOT): [00-Project-Overview.md §12](00-Project-Overview.md#12-document-set--cross-references).
