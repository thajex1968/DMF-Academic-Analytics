# CLAUDE.md — DMF Learning Analytics Platform (DLAP)

*(formerly "DMF Academic Analytics" — module domain: `onet.dmf.ac.th`)*

## Project Status

**Documentation phase — frozen.** No application code exists yet. The full requirement,
architecture, and data-model documentation set lives under `docs/`, is the single source of truth,
and is **frozen as the DLAP Documentation Baseline v2.0.0** — see
[docs/00-Project-Overview.md §13](docs/00-Project-Overview.md#13-documentation-freeze) for the
freeze record and manifest. Frozen means further documentation changes are new, explicit revisions
(a new Revision History row and version bump), not silent edits — it does not mean implementation
is blocked; this baseline is what [docs/00-Project-Overview.md §9
Roadmap](docs/00-Project-Overview.md#9-roadmap) Phase 1 was waiting on.

## What This Project Is

A DMF Platform module built around **the student**, not any single exam: it tracks a student's
learning history from Grade 1 through Grade 6, with an assessment (O-NET, and — in future phases
only, not v1.0 — NT, RT, LAS, Pre/Mid/Post-Test, Classroom/Reading/Writing/Competency Assessment)
recorded as one event in that history, not the organizing principle of the system. **Version 1.0
implements exactly one assessment type: O-NET, Grade 6 (ป.6),** at
โรงเรียนชุมชนดงมะไฟเจริญศิลป์ (school code `47010005`, Sakon Nakhon). It ingests official O-NET
score exports, validates them, maps every test item to its national learning standard
(`สาระ → มาตรฐาน → ตัวชี้วัด`), and renders role-based analytics dashboards for teachers and the
school director. See [docs/00-Project-Overview.md](docs/00-Project-Overview.md) for the full
reframing rationale and [docs/Architecture-Decision-Record.md, ADR-006](docs/Architecture-Decision-Record.md#adr-006--why-a-generic-student-centric-assessment-schema)
for why the schema and architecture generalize beyond O-NET while the v1.0 feature scope does not.

**Important:** `DMF` is short for **Dong Mafai** (ดงมะไฟ) — the school's name root and the
platform brand (`dmf-core`, `*.dmf.ac.th`) — not "Data Management Framework". An earlier draft
document got this wrong; see [docs/archive/01-PRD-legacy.md](docs/archive/01-PRD-legacy.md).

**Naming note:** internal identifiers — the `ONET-DOC-` document ID prefix, the `dmf_academic`
database name, and the `onet.dmf.ac.th` domain — are deliberately **not** renamed to "DLAP" or
similar. They predate the product rename and are kept for backward compatibility and traceability,
per the Backward Compatibility principle in
[docs/Architecture-Principles.md](docs/Architecture-Principles.md#8-backward-compatibility). The
config env-var prefix is the one exception: it was renamed from `ONET_` to **`DLAP_`** during
Module 2 implementation, because — unlike the domain and database name — nothing had ever deployed
against it, so there was no real backward-compatibility cost to weigh against matching the actual
product name. See [decisions/IDR-006](decisions/IDR-006-dlap-env-prefix.md) for the full
reasoning.

## Tech Stack

* **Backend:** PHP 8.3, built on the shared `dmf/core` Composer library (Auth, Database, HTTP,
  Validation, Security, Config, Logger).
* **Frontend:** Bootstrap 5 + Chart.js, vanilla JavaScript (no framework), consumed via an internal
  REST API — same pattern as the sibling `grade.dmf.ac.th` portal.
* **Database:** MySQL/MariaDB, `dmf_academic`, `utf8mb4`, InnoDB. Named generically because the
  schema is assessment-type-agnostic **and** student-centric (student is the primary entity; an
  assessment is an event) — see
  [docs/03-Database-Design.md §4](docs/03-Database-Design.md#4-table-definitions--assessment-framework).
* **Architecture:** Modular Monolith (not microservices) — see
  [docs/02-System-Architecture.md](docs/02-System-Architecture.md) for why.
* **Hosting:** Shared DirectAdmin/cPanel hosting — no Redis, no container orchestration, no
  dedicated servers, no long-running workers outside cron. Every architectural decision in this
  project is checked against that constraint.

## Relationship to the DMF Platform

This is a new, independent Composer project (own codebase, own `dmf_academic` database) that depends
on `dmf/core` exactly as `grade.dmf.ac.th` does. It does **not** share a database or runtime with
any sibling `*.dmf.ac.th` portal (`grade`, `cares`, `eleave`, `library`, `plant`, `smart`,
`timetable`). See:

* `../dmf-core/docs/platform-architecture.md` — the platform's own architecture, layer model, and
  hosting constraints, which this project inherits without deviation.
* `../dmf-core/docs/modules.md` — the `dmf-core` module catalogue this project depends on.
* `../grade.dmf.ac.th/CLAUDE.md` — the reference implementation this project's conventions
  (namespace root `DMF\`, directory layout, coding standards) are aligned with.

## Documentation Map

| Document | Contents |
|---|---|
| [ARCHITECTURE.md](ARCHITECTURE.md) (project root) | Concern-based "where do I look" router — check here first if you don't already know which document has your answer. |
| [docs/00-Project-Overview.md](docs/00-Project-Overview.md) | Identity, background, scope, platform relationship, roadmap. |
| [docs/01-PRD.md](docs/01-PRD.md) | Full product requirements — functional (FR-001–FR-020), non-functional, roles, workflows, KPIs. |
| [docs/02-System-Architecture.md](docs/02-System-Architecture.md) | Modular monolith design: module decomposition, layers, import pipeline, deployment, security. |
| [docs/03-Database-Design.md](docs/03-Database-Design.md) | `dmf_academic` schema: full ER diagram, table definitions, indexing, retention — student-centric, longitudinal, assessment-type-agnostic. |
| [docs/Domain-Model.md](docs/Domain-Model.md) | Conceptual chain: Student → Enrollment → Assessment → Question → Learning Standard → Learning Content → Mastery → Recommendation. |
| [docs/Business-Flow.md](docs/Business-Flow.md) | Business value chain: Learning Evidence → Validation → Normalization → Storage → Analytics → Insight → Recommendation → Intervention → Improvement. |
| [docs/Architecture-Decision-Record.md](docs/Architecture-Decision-Record.md) | ADR-001–ADR-006: why Modular Monolith, PHP 8.3, MySQL, Bootstrap 5, Chart.js, and the generic student-centric schema. |
| [docs/Architecture-Principles.md](docs/Architecture-Principles.md) | Cross-cutting engineering principles (SSOT, DRY, KISS, YAGNI, Module Isolation, Shared Components, Convention over Configuration, Backward Compatibility) every document and future change follows. |
| [docs/Data-Dictionary.md](docs/Data-Dictionary.md) | Field-level business meaning and validation rules for every table. |
| [docs/Naming-Convention.md](docs/Naming-Convention.md) | Naming rules for tables, columns, classes, methods, API routes, files. |
| [docs/Documentation-QA-Report.md](docs/Documentation-QA-Report.md) | Audit of this document set for consistency, correctness, and cross-reference integrity. |
| [docs/Release-Notes.md](docs/Release-Notes.md) | Planned/shipped release history (v0.1.0, v0.2.0, ...) — versioned independently of this documentation baseline. |
| [docs/DECISION_TREE.md](docs/DECISION_TREE.md) | Routes a piece of Learning Evidence to the engine that scores it (Question / Rubric / Evidence / Observation Engine) — only Question Engine is built in v1.0. |
| [docs/rfcs/README.md](docs/rfcs/README.md) | Requests For Change (RFC) — scope-change proposals (e.g., activate a new assessment type), one tier above ADR/IDR. |
| [docs/archive/01-PRD-legacy.md](docs/archive/01-PRD-legacy.md) | Superseded early draft — historical reference only, do not use. |
| [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) (project root) | Roadmap → Task → Implementation Order → Dependencies → Coding Rules → Definition of Done → QA Checklist — read this before starting Phase 1. |
| [decisions/README.md](decisions/README.md) (project root) | Implementation Decision Records (IDR) — concrete library/pattern choices, distinct from the architecture-level ADRs above. |
| [PROJECT_BOARD.md](PROJECT_BOARD.md) (project root) | Living sprint tracker for current implementation work — not part of the frozen documentation baseline. |
| [START_SESSION.md](START_SESSION.md) (project root) | The fixed procedure to run at the start of every implementation session — read this, then start working. |

All active documents share the `ONET-DOC-` ID prefix, are versioned together, and cross-reference
each other; a scope or architecture change must be reflected in all of them — see
[docs/Architecture-Principles.md §1](docs/Architecture-Principles.md#1-single-source-of-truth-ssot)
(Single Source of Truth).

## Planned Repository Structure

Once implementation starts (scaffolded from `dmf-template`), the layout will be:

```
onet.dmf.ac.th/
├── app/            # DMF\ namespace: Action, Student, Import, Standards, Analytics, Reporting, Diagnostics, Notification, Repository
├── config/         # app.php, auth.php, database.php — env-var driven, no hardcoded secrets
├── database/       # schema.sql, migrations/
├── docs/           # This document set
├── public_html/    # index.html (SPA shell) + api/ (front controller)
├── storage/        # cache/, logs/, imports/ — never web-accessible
├── tests/          # Unit/, Integration/
├── composer.json
├── phpstan.neon
├── phpunit.xml
└── .phpcs.xml
```

Full rationale: [docs/02-System-Architecture.md §5](docs/02-System-Architecture.md#5-repository--directory-structure).

## Conventions to Follow (once code exists)

* Composer namespace root: `DMF\` (matching `grade.dmf.ac.th`, not the `dmf-template` default
  `App\`).
* PSR-12 coding style, enforced via `.phpcs.xml`; PHPStan static analysis; PHPUnit tests under
  `tests/{Unit,Integration}` — mirrors `dmf-core`'s own `composer.json` scripts (`test`, `lint`,
  `analyse`).
* Depend on `dmf-core` **contracts**, not concretions. Do not reimplement auth, DB access, or
  validation primitives that `dmf-core` already provides.
* Secrets (`DB_PASS`, `TOKEN_SECRET`, `LLM_API_KEY`) via environment variables only — never
  hardcoded, never committed.
* Reference data (the `สาระ/มาตรฐาน/ตัวชี้วัด` standards catalogue, and the `assessment_types`
  reference table) is data, changed through the Approval Flow in
  [docs/01-PRD.md §21](docs/01-PRD.md#21-core-product-capabilities), never hardcoded in
  application logic.
* Full naming rules (tables, columns, classes, methods, API routes, files):
  [docs/Naming-Convention.md](docs/Naming-Convention.md).

## Domain Glossary

See [docs/00-Project-Overview.md §11](docs/00-Project-Overview.md#11-glossary) for the full shared
glossary (DLAP, Assessment, Longitudinal Learning Analytics, O-NET, สทศ/NIETS, ป.1–ป.6,
สาระ/มาตรฐาน/ตัวชี้วัด, ปพ.).
