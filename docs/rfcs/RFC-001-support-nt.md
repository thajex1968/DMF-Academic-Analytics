# RFC-001 — Support NT

**Status:** Proposed
**Author:** DMF Platform Team
**Date:** 2026-07-02

## Summary

Activate **NT (National Test)** as a second supported assessment type, alongside O-NET, for the
grade level(s) NT is administered at.

## Motivation

[00-Project-Overview.md §3](../00-Project-Overview.md#3-background) states the platform's reason
for existing as tracking a student's learning history "across every assessment they ever sit," not
just O-NET. NT is administered earlier in a student's primary-school years than O-NET (commonly
around ป.3), which is exactly the kind of earlier data point the longitudinal design in
[ADR-006](../Architecture-Decision-Record.md#adr-006--why-a-generic-student-centric-assessment-schema)
exists to eventually connect to a student's later O-NET result on the same `Learning Standard`.
Without NT, the platform's "Grade 1–6" framing ([00-Project-Overview.md
§6](../00-Project-Overview.md#6-scope)) has only one data point (ป.6) actually populated —
activating NT is the most direct way to start making the longitudinal claim real rather than
aspirational.

## Scope of Change

* Set `assessment_types.is_active = 1` for the `NT` row (already seeded, per
  [03-Database-Design.md §4](../03-Database-Design.md#4-table-definitions--assessment-framework) —
  no new reference row needed).
* Build an NT-specific import template
  ([02-System-Architecture.md §7](../02-System-Architecture.md#7-import-pipeline-architecture)'s
  per-academic-year-*and-per-assessment-type* template registry) for whatever file format the
  issuing body (also สทศ/NIETS, per the same national testing infrastructure as O-NET) provides NT
  results in.
* Map NT's item-to-indicator relationships into `questions`/`learning_indicators`, for whichever
  subjects and grade level NT covers.
* Extend the standards-catalogue seed data ([03-Database-Design.md
  §5](../03-Database-Design.md#5-table-definitions--standards-catalogue)) to include indicators at
  the grade level NT is administered at, if not already present (the schema already supports
  `grade_level` 1–6 on `learning_indicators`; only the *data* for grades below 6 is currently
  missing, per [00-Project-Overview.md §6](../00-Project-Overview.md#6-scope)'s note that v1.0 data
  is "ป.6 only").
* Add NT-specific FRs to [01-PRD.md](../01-PRD.md), mirroring FR-003–FR-013 but scoped to NT (import,
  validate, map, aggregate) — a new FR range, not a rewrite of the O-NET FRs.

## Impact Analysis

**Schema:** none required. `assessment_types`, `assessments`, `questions`, `student_scores`, and
`student_question_responses` are all already assessment-type-agnostic
([03-Database-Design.md §1](../03-Database-Design.md#1-design-principles)) — this is the specific
claim [ADR-006](../Architecture-Decision-Record.md#adr-006--why-a-generic-student-centric-assessment-schema)
made, and NT is a same-shape item-based test (multiple-choice, one correct answer per item, same as
O-NET), so it is the least-risky possible test of that claim.

**Enrollment data:** `student_enrollments` and `classrooms` already support any `grade_level` 1–6
([03-Database-Design.md §3](../03-Database-Design.md#3-table-definitions--organizational)); if NT is
administered at a grade this school does not yet have classroom/enrollment records for, those
records need to be created (a data-entry task, not a schema task).

**Import/Analytics/Dashboard code:** requires new work — a new parser template, and the Dashboard
needs an assessment-type filter/selector it does not currently have (v1.0's dashboards implicitly
assume "the" assessment is O-NET, per [01-PRD.md §22](../01-PRD.md#22-dashboard--visualization) not
yet needing to distinguish). This is real, non-trivial implementation work, even though the
*schema* needs none.

## Alternatives Considered

* **Wait until the multi-school phase to activate any second assessment type** — rejected as the
  default recommendation. There is no dependency between multi-school readiness
  ([00-Project-Overview.md §9](../00-Project-Overview.md#9-roadmap), Phase 5) and activating a
  second assessment type for the *existing* single school; bundling them would delay a
  student-value improvement for an unrelated reason.
* **Activate NT and RT together in one RFC** — considered, rejected in favor of two separate RFCs
  ([RFC-001](RFC-001-support-nt.md), [RFC-002](RFC-002-support-rt.md)). Each is independently
  approvable and independently valuable; a school might reasonably want one before the other.

## Approval Path

School Director (pedagogical value, timing) and System Administrator (implementation capacity),
per the Approval Flow in [01-PRD.md §21](../01-PRD.md#21-core-product-capabilities). No curriculum
editor sign-off is needed for *this* RFC specifically unless new standards-catalogue indicator rows
must be added for NT's grade level.

## Out of Scope / Non-Goals

* This RFC does not propose activating any assessment type other than NT.
* This RFC does not propose populating `student_standard_mastery` — that remains gated on the
  per-student report feature existing at all, independent of how many assessment types are active
  ([03-Database-Design.md §9](../03-Database-Design.md#9-table-definitions--aggregation--materialized-summaries)).

## Cross-References

* [00-Project-Overview.md §6](../00-Project-Overview.md#6-scope) — where `NT` is already listed as
  a reserved code.
* [ADR-006](../Architecture-Decision-Record.md#adr-006--why-a-generic-student-centric-assessment-schema) —
  the architectural claim this RFC's Impact Analysis tests.
* [RFC-002](RFC-002-support-rt.md) — the equivalent proposal for RT.
