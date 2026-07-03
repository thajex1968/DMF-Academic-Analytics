# Release Notes

**DMF Learning Analytics Platform (DLAP)**

| | |
|---|---|
| **Document ID** | ONET-DOC-012 |
| **Version** | 1.0.0 |
| **Status** | Frozen — DLAP Documentation Baseline v2.0.0 |
| **Date** | 2026-07-02 |
| **Author** | DMF Platform Team |
| **Related documents** | [00-Project-Overview](00-Project-Overview.md) · [01-PRD](01-PRD.md) · [IMPLEMENTATION_GUIDE](../IMPLEMENTATION_GUIDE.md) · [PROJECT_BOARD](../PROJECT_BOARD.md) |

## Revision History

| Version | Date | Description | Author |
|---|---|---|---|
| 1.0.0 | 2026-07-02 | Initial release, added as a Post-Freeze Amendment to the DLAP Documentation Baseline v2.0.0 (see [00-Project-Overview.md §13](00-Project-Overview.md#13-documentation-freeze)). Establishes the release-notes format and the planned content of v0.1.0 and v0.2.0. | DMF Platform Team |

## About This Document

**No release has shipped yet.** No application code exists at the time this document is written —
see [CLAUDE.md](../CLAUDE.md)'s Project Status. Every entry below marked **Planned** describes what
that release is intended to contain, derived directly from
[IMPLEMENTATION_GUIDE.md §2](../IMPLEMENTATION_GUIDE.md#2-task)'s task breakdown — it is a release
*plan*, not a release *record*, and is written that way deliberately rather than claiming
completed work that hasn't happened. When a version actually ships, its entry is edited to
**Released**, with its actual date and any deviation from the plan noted — the entry is not
rewritten as if the plan had been the history all along, per
[Architecture-Principles.md §1](Architecture-Principles.md#1-single-source-of-truth-ssot).

**Versioning policy:** Semantic Versioning 2.0 (`MAJOR.MINOR.PATCH`), per
[01-PRD.md, Appendix](01-PRD.md#versioning). Application releases start at `0.1.0`, independent of
the documentation baseline's own version number (currently v2.0.x) — the two track different
things: the baseline versions the *specification*, these entries version the *software*. `1.0.0` is
reserved for the first release that delivers the complete v1.0 functional scope defined in
[01-PRD.md §6](01-PRD.md#6-scope) (O-NET, Grade 6, end to end) — not before.

## Table of Contents

1. [Unreleased](#1-unreleased)
2. [v0.2.0 — Import & Validation (Planned)](#2-v020--import--validation-planned)
3. [v0.1.0 — Foundation (Planned)](#3-v010--foundation-planned)
4. [Cross-References](#4-cross-references)

---

## 1. Unreleased

Nothing is in progress ahead of v0.1.0 as of this document's date — [Phase
1](../IMPLEMENTATION_GUIDE.md#1-roadmap) has not started. Track current work-in-progress on
[PROJECT_BOARD.md](../PROJECT_BOARD.md), not here; this document only records what a *finished*
version contains, once it's finished (or, until then, planned to contain).

## 2. v0.2.0 — Import & Validation (Planned)

**Maps to:** [IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md) Phase 2, Tasks T2.1–T2.6.

**Planned to add:**
* O-NET PDF/Excel/CSV import (FR-003/FR-004/FR-005), via `PdfParser`/`ExcelParser`/`CsvParser` —
  see [decisions/IDR-001](../decisions/IDR-001-phpspreadsheet-for-excel-import.md) for the Excel
  parsing library.
* Structural and content validation (FR-006), duplicate-import detection (FR-007), and the import
  audit log (FR-008).
* Item-to-Learning-Standard mapping (FR-009) — the "Normalization" stage of
  [Business-Flow.md §4](Business-Flow.md#4-normalization).
* The cron-driven job runner and commit transaction — "Storage,"
  [Business-Flow.md §5](Business-Flow.md#5-storage).

**Depends on:** v0.1.0 (Student & Enrollment module and schema must exist first — see
[IMPLEMENTATION_GUIDE.md §3](../IMPLEMENTATION_GUIDE.md#3-implementation-order)).

**Explicitly not in this release:** any Analytics, Dashboard, or Recommendation capability — v0.2.0
can accept and store an O-NET file, but nothing yet reads the result back out. That begins at the
release that completes Phase 3.

## 3. v0.1.0 — Foundation (Planned)

**Maps to:** [IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md) Phase 1, Tasks T1.1–T1.7.

**Planned to add:**
* Project scaffolded from `dmf-template`, `DMF\` namespace root, `dmf/core` wired as a Composer
  dependency.
* The `dmf_academic` schema created in full ([03-Database-Design.md](03-Database-Design.md)),
  including the assessment framework and student-enrollment tables — no application feature reads
  or writes most of it yet at this stage, but every table exists.
* `assessment_types` seeded (`ONET` active; ten reserved codes present but inactive).
* The **Student & Enrollment module** — the foundational module every later release builds on
  ([02-System-Architecture.md §3](02-System-Architecture.md#3-module-decomposition)).
* Staff authentication (FR-001) and the role-scoped dashboard shell's routing/auth-check (FR-002) —
  no chart or data content behind it yet.

**Depends on:** nothing — this is the first release.

**Explicitly not in this release:** any import, validation, or analytics capability. A person can
log in and see an empty, correctly-access-scoped shell; that is the entire v0.1.0 surface.

## 4. Cross-References

* The task-level detail behind every "Planned to add" bullet above:
  [IMPLEMENTATION_GUIDE.md §2](../IMPLEMENTATION_GUIDE.md#2-task).
* Current sprint-level, day-to-day progress: [PROJECT_BOARD.md](../PROJECT_BOARD.md).
* The functional requirements each release delivers: [01-PRD.md](01-PRD.md).
* Why a release depends on the one before it: [IMPLEMENTATION_GUIDE.md
  §3](../IMPLEMENTATION_GUIDE.md#3-implementation-order).
