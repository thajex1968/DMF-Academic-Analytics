# Architecture Decision Record

**DMF Learning Analytics Platform (DLAP)** *(formerly "DMF Academic Analytics" — module domain: `onet.dmf.ac.th`)*

| | |
|---|---|
| **Document ID** | ONET-DOC-004 |
| **Version** | 1.2.0 |
| **Status** | Frozen — DLAP Documentation Baseline v2.0.0 |
| **Date** | 2026-07-02 |
| **Author** | DMF Platform Team |
| **Related documents** | [00-Project-Overview](00-Project-Overview.md) · [01-PRD](01-PRD.md) · [02-System-Architecture](02-System-Architecture.md) · [03-Database-Design](03-Database-Design.md) · [Architecture-Principles](Architecture-Principles.md) · [decisions/README](../decisions/README.md) |

## Revision History

| Version | Date | Description | Author |
|---|---|---|---|
| 1.0.0 | 2026-07-02 | Initial release. Records the five foundational technology decisions made before implementation: Modular Monolith, PHP 8.3, MySQL/MariaDB, Bootstrap 5, Chart.js. | DMF Platform Team |
| 1.1.0 | 2026-07-02 | Renamed to DMF Learning Analytics Platform (DLAP). Added **ADR-006 — Why a Generic, Student-Centric Assessment Schema?**, recording the decision behind the [03-Database-Design.md](03-Database-Design.md) v2.0.0 redesign (`assessment_types`/`assessments`, `student_enrollments`, `student_standard_mastery`). The five original ADRs are unchanged in substance. | DMF Platform Team |
| 1.1.1 | 2026-07-02 | QA fix (see [Documentation-QA-Report.md](Documentation-QA-Report.md)): corrected ADR-006's assessment-type count description from "eleven reserved codes" to "eleven codes total, ten reserved." Frozen as part of the DLAP Documentation Baseline v2.0.0 ([00-Project-Overview.md §13](00-Project-Overview.md#13-documentation-freeze)). | DMF Platform Team |
| 1.2.0 | 2026-07-02 | Post-Freeze Amendment. §1 and §8 updated to split "decisions made during implementation" into architecture-level (still `docs/adr/`) and implementation-level (now `decisions/`, Implementation Decision Records) — see [decisions/README.md](../decisions/README.md) for the new distinction. No change to ADR-001–ADR-006's substance. | DMF Platform Team |

## Table of Contents

1. [Purpose & Format](#1-purpose--format)
2. [ADR-001 — Why Modular Monolith?](#adr-001--why-modular-monolith)
3. [ADR-002 — Why PHP 8.3?](#adr-002--why-php-83)
4. [ADR-003 — Why MySQL/MariaDB?](#adr-003--why-mysqlmariadb)
5. [ADR-004 — Why Bootstrap 5?](#adr-004--why-bootstrap-5)
6. [ADR-005 — Why Chart.js?](#adr-005--why-chartjs)
7. [ADR-006 — Why a Generic, Student-Centric Assessment Schema?](#adr-006--why-a-generic-student-centric-assessment-schema)
8. [Future ADRs](#8-future-adrs)

---

## 1. Purpose & Format

This document records the architecturally significant decisions behind [01-PRD.md](01-PRD.md) and
[02-System-Architecture.md](02-System-Architecture.md) — *why* each foundational technology was
chosen, not just *what* was chosen. Each entry follows the same structure: **Status**, **Context**,
**Decision**, **Alternatives Considered**, **Consequences**.

These six ADRs were made together, before implementation, as part of the initial architecture
baseline (see [02-System-Architecture.md §18](02-System-Architecture.md#18-architecture-decision-records)).
Further architecture-level decisions made *during* implementation follow `dmf-core`'s existing
convention of one file per decision under `docs/adr/`; narrower, implementation-level decisions
(which library, which version, which integration pattern) go to
[`decisions/`](../decisions/README.md) as Implementation Decision Records (IDR) instead — see
[§8](#8-future-adrs) below for the full split.

---

## ADR-001 — Why Modular Monolith?

**Status:** Accepted — 2026-07-02

**Context:** The module must run on the same shared DirectAdmin/cPanel hosting used by every other
`*.dmf.ac.th` portal: no container orchestration, no service mesh, no per-service scaling, no
shell access beyond FTP/SSH file upload (`dmf-core/docs/platform-architecture.md §9`). The problem
domain itself (import → validate → map → aggregate → present → report) has clear internal
boundaries, but the dataset is small — one school, one grade level, a few hundred students per
year — and the team is small.

**Decision:** Build a **Modular Monolith**: one deployable Composer project, one database, one PHP
runtime, internally decomposed into cohesive modules (`Student & Enrollment`, `Import`,
`Standards`, `Analytics`, `Dashboard`, `Reporting`, `Diagnostics`, `Notification`) with a
documented, one-directional dependency graph, enforced by code review and static analysis rather
than network boundaries. Full decomposition:
[02-System-Architecture.md §3](02-System-Architecture.md#3-module-decomposition).

**Alternatives Considered:**
* **Microservices** — rejected. Shared hosting provides no orchestration platform, no service
  discovery, and no message broker; running even two independent PHP processes reliably on a
  cPanel plan is already awkward. The operational cost would be paid entirely to manage
  complexity a single-school dataset does not need.
* **Undifferentiated single script** (the style of `grade.dmf.ac.th`'s existing
  `grade_api.php`, ~1,100 lines in one file) — rejected for a *new* project of this scope. It
  works for a narrower CRUD-style portal, but this module's pipeline (parsing, validation,
  standards mapping, statistics, reporting) has enough distinct responsibility that one file
  would become difficult to test and to extend toward NT/RT/LAS later.

**Consequences:** Deployment stays as simple as the rest of the DMF Platform (FTP/SSH upload, no
new infrastructure to operate). The cost is that all modules share one PHP process and memory
space — a defect in one module can affect the whole request — mitigated by PHPUnit coverage
targets and PHPStan analysis on module boundaries
([01-PRD.md §20](01-PRD.md#20-non-functional-requirements)).

---

## ADR-002 — Why PHP 8.3?

**Status:** Accepted — 2026-07-02

**Context:** `dmf-core` requires PHP `^8.1`; the reference implementation `grade.dmf.ac.th`
requires PHP `>=8.2`. Shared hosting providers used by the DMF Platform expose a cPanel/
DirectAdmin PHP-version selector supporting multiple concurrent PHP versions per domain, so each
portal can choose independently.

**Decision:** Target **PHP 8.3** for this module — the newest stable version already available
through the same hosting providers' PHP selector at project start, and a strict superset of the
`^8.1` baseline `dmf-core` requires, so no compatibility work is needed to consume it.

**Alternatives Considered:**
* **PHP 8.1** (the `dmf-core` floor) — rejected as the *target*, though it remains the
  *compatibility floor* `dmf-core` itself must keep supporting. Building the new module on 8.1
  specifically would forgo readonly property promotion ergonomics and newer standard-library
  additions available in later 8.x releases, for no hosting-availability benefit.
* **PHP 8.4** — rejected for this project's start date: it was not yet the hosting-provider
  default/stable offering the DMF Platform's shared hosts had rolled out, and `grade.dmf.ac.th`'s
  own floor (`>=8.2`) gave no reason to chase the newest point release.

**Consequences:** The module can rely on PHP 8.1+ language features without a shim; `dmf-core`
itself remains usable by other, older-PHP sibling portals since its own composer constraint is
unaffected by this project's choice. Before each deployment, confirm the target hosting account's
PHP selector actually offers 8.3 (shared hosting providers vary; this is an operational
pre-deployment check, not an architectural risk).

---

## ADR-003 — Why MySQL/MariaDB?

**Status:** Accepted — 2026-07-02

**Context:** `dmf-core`'s `Database\Connection` wraps PDO and is already proven against MySQL in
production (`dmf_grade`, per `grade.dmf.ac.th`). DirectAdmin/cPanel shared hosting universally
provisions MySQL or MariaDB as the included database engine; alternative engines typically are not
offered, or require a paid add-on.

**Decision:** **MySQL/MariaDB**, `utf8mb4` character set, `InnoDB` storage engine — identical to
`dmf_grade`, now named `dmf_academic` (see [03-Database-Design.md](03-Database-Design.md)).

**Alternatives Considered:**
* **PostgreSQL** — rejected. Not reliably available on the Thai school shared-hosting plans this
  platform targets, and `dmf-core`'s `Connection`/`QueryBuilder` contracts would need a second,
  untested driver implementation for a benefit (richer SQL features) this project doesn't need.
* **SQLite** — rejected. File-based SQLite does not give the concurrent-write safety needed when
  multiple teachers import files around the same time, and shared hosting file-locking behavior
  for SQLite on network-attached storage is unreliable.

**Consequences:** Full consistency with the sibling reference implementation's tooling
(`mysqldump` backups, `dmf-core`'s existing `Connection` class, PDO prepared statements
throughout). One consequence to track: MySQL 8.0.16+ and MariaDB 10.2+ enforce `CHECK`
constraints, but earlier MariaDB versions parse and silently ignore them — the schema therefore
duplicates score-range validation in the application layer rather than relying on `CHECK` alone
(see [03-Database-Design.md §13](03-Database-Design.md#13-data-integrity-rules)).

---

## ADR-004 — Why Bootstrap 5?

**Status:** Accepted — 2026-07-02

**Context:** The module needs role-scoped dashboards, forms, and tables that meet a WCAG 2.1 AA
accessibility target ([01-PRD.md §20](01-PRD.md#20-non-functional-requirements)), deployed by
FTP/SSH file upload with no Node-based build pipeline in the hosting environment. `grade.dmf.ac.th`
uses a plain vanilla-JS SPA with no CSS framework, built for a much narrower single-page login/view
flow than this module's multi-screen analytics dashboard.

**Decision:** **Bootstrap 5**, loaded as static CSS/JS assets (CDN or vendored, no build step),
providing grid layout, forms, tables, and navigation components out of the box.

**Alternatives Considered:**
* **Tailwind CSS** — rejected. Tailwind's utility-class model is normally paired with a
  PostCSS/JIT build step to purge unused classes; introducing a Node-based build pipeline
  conflicts with the FTP-upload deployment model this platform otherwise avoids entirely
  ([02-System-Architecture.md §13](02-System-Architecture.md#13-deployment-architecture)).
* **A JavaScript SPA framework (React/Vue)** — rejected. It would require a bundler and a build
  step for the same reason, add a much larger client payload than an admin dashboard with a
  handful of screens needs, and diverge from the "vanilla JS, no framework" convention already
  established by `grade.dmf.ac.th` and `dmf-template`.

**Consequences:** Fast to build accessible, responsive layouts (320px through desktop,
[01-PRD.md §20](01-PRD.md#20-non-functional-requirements)) without new tooling. The trade-off is
less visual differentiation than a bespoke design system — acceptable for an internal staff-facing
analytics tool, not a public-facing product.

---

## ADR-005 — Why Chart.js?

**Status:** Accepted — 2026-07-02

**Context:** The dashboard needs trend lines, a classroom×standard heatmap, radar charts, item-
analysis bar charts, and doughnut/progress indicators
([01-PRD.md §22](01-PRD.md#22-dashboard--visualization)), rendered client-side in the browser —
shared hosting has no headless-browser or server-side chart-image rendering capacity to fall back
on.

**Decision:** **Chart.js**, using the community `chartjs-chart-matrix` plugin specifically for the
classroom-standard heatmap (Chart.js core does not ship a matrix chart type).

**Alternatives Considered:**
* **D3.js** — rejected. Far more powerful and flexible, but every chart type Chart.js provides
  out of the box would need to be hand-built from D3's lower-level primitives — more code to
  write and maintain for a small team, for chart types (line, bar, radar, doughnut) that are
  already standard, not bespoke.
* **Server-side chart image generation** (e.g., rendering PNGs via GD/Imagick on the PHP side) —
  rejected for the interactive dashboard. It loses tooltips, hover drill-down, and client-side
  filtering, and shared hosting's GD/Imagick capability is inconsistent across providers. (A
  similar, narrower technique is still used for print-ready PDF exports —
  [02-System-Architecture.md §12](02-System-Architecture.md#12-reporting--export-architecture) —
  where interactivity is not needed.)

**Consequences:** Lightweight, canvas-based rendering with wide community support and a simple
vanilla-JS integration path. The one dependency to track across upgrades is the third-party
`chartjs-chart-matrix` plugin (not part of Chart.js core), since it must stay compatible with
whichever Chart.js major version the project pins.

---

## ADR-006 — Why a Generic, Student-Centric Assessment Schema?

**Status:** Accepted — 2026-07-02

**Context:** The project was originally scoped, designed, and even named as an "O-NET Analytics
System" — a schema and API built around one national exam. Partway through the documentation
phase, the school clarified the actual need: know how a *specific student* is progressing from
Grade 1 through Grade 6, across every assessment they ever sit — O-NET, but also NT, RT, LAS,
Pre/Mid/Post-Tests, and classroom-level Reading/Writing/Competency assessments the school already
runs informally. An exam-centric schema (one row per exam administration, students joined in as a
detail) answers "how did this cohort do on this year's O-NET" well, but cannot answer "how has
this one student done on this ตัวชี้วัด across six years and three different assessment types"
without a redesign — exactly the kind of rework [01-PRD.md §9](01-PRD.md#9-current-workflow)'s
underlying problem statement is trying to eliminate, just moved from the school's manual process
into the software itself.

**Decision:** Model the schema so that **the student is the entity everything else is recorded
against, and an assessment is one type of event in that record** ([03-Database-Design.md
§1](03-Database-Design.md#1-design-principles)). Concretely:
* A generic `assessment_types` reference table (eleven codes total — `PRE_TEST`, `MID_TEST`,
  `POST_TEST`, `ONET`, `NT`, `RT`, `LAS`, `CLASSROOM_ASSESSMENT`, `READING_ASSESSMENT`,
  `WRITING_ASSESSMENT`, `COMPETENCY_ASSESSMENT`; only `ONET` is active in v1.0, the other ten are
  reserved) and an `assessments` table (one row per
  administration: type + subject + grade + year) generalize what would otherwise be O-NET-specific
  columns scattered across item and score tables
  ([03-Database-Design.md §4](03-Database-Design.md#4-table-definitions--assessment-framework)).
* A new `student_enrollments` table makes a student's grade/classroom history across Grade 1–6
  first-class, queryable data, rather than a single "current classroom" pointer with no history
  ([03-Database-Design.md §3](03-Database-Design.md#3-table-definitions--organizational)).
* A new `student_standard_mastery` table gives every future assessment type a ready-made place to
  record per-student, per-indicator performance over time, without altering its shape
  ([03-Database-Design.md §9](03-Database-Design.md#9-table-definitions--aggregation--materialized-summaries)).
* A new **Student & Enrollment** module owns this data as a foundational, base-level module that
  `Import` and `Analytics` depend on — no other module queries `students` directly
  ([02-System-Architecture.md §3](02-System-Architecture.md#3-module-decomposition)).

**Alternatives Considered:**
* **Keep the v1.1.0 exam-centric-but-generalized schema (`exam_types`/`exams`) as-is** — rejected.
  It already generalized *which exam*, but still modeled a student's data as something attached to
  an exam administration, not the reverse. It could not represent "this student's Grade 3 NT score
  and Grade 6 O-NET score on the same ตัวชี้วัด" without a student-history table anyway — so not
  making this change now would only defer the same redesign to whenever the second assessment
  type is actually built, at higher cost (with live data to migrate instead of documentation to
  edit).
* **A single wide `student_history` table** covering enrollment, scores, and mastery in one
  table — rejected. It would violate normal form for no benefit: enrollment (once per year),
  scores (once per assessment), and mastery (once per indicator per year) have different
  cardinalities relative to a student, and collapsing them would mean most columns are NULL most
  of the time, and every partial update would risk touching unrelated data — the opposite of
  Module Isolation ([Architecture-Principles.md §3](Architecture-Principles.md#3-module-isolation)).
* **Build only for O-NET now, redesign later when NT/RT are actually funded** — rejected as the
  default, though it is the honest fallback if this generalization turns out to be premature. The
  deciding factor: the redesign this ADR describes costs nothing extra in v1.0 (same number of
  *implemented* features, same FR-001–FR-020 scope — see
  [01-PRD.md §7](01-PRD.md#7-out-of-scope)), because it is purely a naming and structural choice
  made once, before any code or production data exists. Paying that cost after real assessment
  data is live would be substantially more expensive (see the migration note in
  [03-Database-Design.md §16](03-Database-Design.md#16-migration-strategy)).

**Consequences:** No v1.0 functional requirement changed — O-NET, Grade 6 remains the only
implemented assessment type ([01-PRD.md §6](01-PRD.md#6-scope)). The cost is a schema with two
tables (`student_enrollments`, `student_standard_mastery`) and several reserved reference rows
that v1.0 does not populate or read — an explicit, documented exception to YAGNI
([Architecture-Principles.md §7](Architecture-Principles.md#7-yagni--you-arent-gonna-need-it)),
justified because the alternative is a guaranteed future migration rather than a merely possible
one, given the school's stated intent to track NT/RT/LAS and classroom assessments. The benefit is
that activating a reserved assessment type is expected to be a data and import-template change,
not a schema migration — this expectation is itself a testable claim, tracked as a design-review
success metric in [00-Project-Overview.md §10](00-Project-Overview.md#10-success-metrics).

---

## 8. Future ADRs

This file remains the record of the six foundational, pre-implementation decisions only. Two
different kinds of decision get made after it, and they are recorded in two different places —
see [decisions/README.md §1](../decisions/README.md#1-adr-vs-idr) for the full distinction:

* **Further architecture-level decisions** — ones that would change a module boundary, a data-flow
  pattern, or a platform-wide constraint (e.g., "introduce a caching layer if the multi-school
  phase's read volume needs it," which would revisit the "no Redis" constraint this baseline
  assumes) — are captured as individual files under `docs/adr/` (one per decision), following the
  convention already established in `dmf-core/docs/adr/`, once such a decision actually arises.
* **Implementation-level decisions** — which specific library, version, or integration pattern
  implements a choice this file already made (e.g., which Excel-parsing library, or exactly how
  Chart.js is wired into the Dashboard module) — are captured as Implementation Decision Records
  (IDR) under [`decisions/`](../decisions/README.md), starting from Task T1.1
  ([IMPLEMENTATION_GUIDE.md §2](../IMPLEMENTATION_GUIDE.md#2-task)) — see
  [decisions/IDR-001](../decisions/IDR-001-phpspreadsheet-for-excel-import.md) through
  [IDR-003](../decisions/IDR-003-pdo-for-database-layer.md) for the first three, recorded ahead of
  Phase 1 while the choices were already clear.
