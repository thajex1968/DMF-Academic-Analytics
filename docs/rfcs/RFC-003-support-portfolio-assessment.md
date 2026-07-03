# RFC-003 — Support Portfolio Assessment

**Status:** Proposed
**Author:** DMF Platform Team
**Date:** 2026-07-02

> **Update, 2026-07-02:** [DECISION_TREE.md §4](../DECISION_TREE.md#4-portfolio--evidence-engine)
> now names the proposed **Evidence Engine** this RFC's scope routes to, and
> [§7](../DECISION_TREE.md#7-open-question-where-evaluation-method-lives-in-the-schema) restates
> this RFC's two schema directions (Option A/B there ≈ the two directions in this RFC's own Impact
> Analysis) alongside a third, `assessment_components`-table option not considered here. The
> follow-on ADR this RFC calls for is still unwritten — this update only narrows what it needs to
> decide.

## Summary

Add a **new** assessment type, `PORTFOLIO_ASSESSMENT` — teacher evaluation of a student's collected
work (writing samples, project work, classroom exercises) against Learning Standards, scored by
rubric rather than by test item. Unlike [RFC-001](RFC-001-support-nt.md) and
[RFC-002](RFC-002-support-rt.md), this is **not** one of the eleven codes
[00-Project-Overview.md §6](../00-Project-Overview.md#6-scope) already reserved — approving this RFC
means adding a twelfth.

## Motivation

Every assessment type this platform currently reserves or has activated is a discrete testing
*event* — a student sits down and takes a test on a specific day. Portfolio assessment is
structurally different: it evaluates work accumulated *over a period* (a semester, a unit), against
the same `Learning Standard`s everything else in this platform already organizes around
([Domain-Model.md §6](../Domain-Model.md#6-learning-standard)). Several DMF Platform schools already
use portfolio-style evaluation informally (per [01-PRD.md
§9](../01-PRD.md#9-current-workflow)-style manual processes, for the subjects and standards it
doesn't test via O-NET/NT/RT) — bringing it into the same longitudinal record this platform
otherwise maintains would close a real gap: right now, only item-testable standards ever get a
mastery signal at all.

## Scope of Change

* Add a new `assessment_types` row: `code = 'PORTFOLIO_ASSESSMENT'`, seeded but **not** active
  until this RFC (and its follow-on ADR, see [Impact Analysis](#impact-analysis)) is approved and
  built — this is itself a small, direct test of
  [ADR-006](../Architecture-Decision-Record.md#adr-006--why-a-generic-student-centric-assessment-schema)'s
  claim that adding an assessment type is "a data change," extended to *adding a new code*, not
  just activating a reserved one.
* Update [00-Project-Overview.md §6](../00-Project-Overview.md#6-scope)'s assessment-type table to
  list a twelfth row.
* Design a portfolio-specific evaluation workflow: a teacher scores a student's portfolio against
  one or more `Learning Standard`s directly, on a rubric scale (not a `correct_choice`), at a
  cadence the teacher controls (not a single fixed assessment date).
* Add portfolio-specific FRs to [01-PRD.md](../01-PRD.md).

## Impact Analysis

Portfolio assessment does not merely lack discrete items, the way [RFC-002](RFC-002-support-rt.md)'s
oral fluency component does — it also lacks a single fixed **assessment date**, which is a
structural assumption `assessments` currently makes: one row per `(assessment_type_id,
subject_code, academic_year)`, implying one administration per year
([03-Database-Design.md §4](../03-Database-Design.md#4-table-definitions--assessment-framework)). A
portfolio can reasonably be scored multiple times within one academic year (e.g., once per
semester, or once per unit) as the work accumulates — closer in shape to
`student_standard_mastery`'s per-indicator, per-year rows
([03-Database-Design.md §9](../03-Database-Design.md#9-table-definitions--aggregation--materialized-summaries))
than to a single `assessments` row.

**This RFC's honest conclusion:** portfolio assessment is a *harder* case than RFC-002's RT gap,
and this RFC recommends the follow-on architecture work be scoped and reviewed as its own ADR
**before** implementation begins — not folded into an IDR, and not assumed to be "just data" the
way activating NT/RT is. Two viable directions for that ADR to evaluate:

1. Allow multiple `assessments` rows per `(assessment_type_id, subject_code, academic_year)` for
   portfolio-type assessments specifically (relaxing the current uniqueness constraint
   conditionally), each portfolio "scoring event" being its own `assessments` row with its own
   `student_scores`.
2. Treat each portfolio scoring event as directly producing `student_standard_mastery`-shaped data
   (skipping `assessments`/`questions` entirely for this assessment type), which would mean
   `student_standard_mastery` needs to be populated by more than the per-student longitudinal
   report feature it's currently scoped to ([03-Database-Design.md
   §9](../03-Database-Design.md#9-table-definitions--aggregation--materialized-summaries)) —
   a scope change to that table's own justification, not just a new data source for it.

This RFC does not pick between the two — that choice is exactly what the recommended follow-on ADR
is for.

## Alternatives Considered

* **Force portfolio assessment into the existing `assessments`-per-year shape** (one row per
  academic year, re-scored in place as more work accumulates within the year) — rejected as the
  *default* recommendation. It would avoid new architecture work, but at the cost of losing
  within-year granularity (a teacher couldn't see how a portfolio score changed from semester 1 to
  semester 2 without a mid-year "correction" that abuses the "never edit a committed assessment"
  rule in [03-Database-Design.md §13](../03-Database-Design.md#13-data-integrity-rules)).
* **Do not pursue portfolio assessment at all** — a legitimate outcome of this RFC's review, given
  it is the first assessment type this platform has considered that isn't a discrete testing event.
  Recorded as an alternative, not a recommendation, because the motivation above (bringing
  currently-untracked standards into the same longitudinal record) is real school value, not a
  speculative one.

## Approval Path

School Director and System Administrator per the standard Approval Flow
([01-PRD.md §21](../01-PRD.md#21-core-product-capabilities)) — **and**, because this RFC recommends
new architecture-level work rather than a pure data/config change, sign-off on this RFC should be
understood as "approved to write the follow-on ADR and bring it back for review," not "approved to
build" — a two-step approval, unlike [RFC-001](RFC-001-support-nt.md) and
[RFC-002](RFC-002-support-rt.md), which are single-step.

## Out of Scope / Non-Goals

* This RFC does not propose a general "recurring/multi-event assessment" abstraction ahead of a
  second concrete need for one — if the follow-on ADR's answer turns out to generalize beyond
  portfolios, that generalization is evaluated then, not speculated here
  ([Architecture-Principles.md §7](../../docs/Architecture-Principles.md#7-yagni--you-arent-gonna-need-it)).
* This RFC does not propose changing `assessments`' existing uniqueness constraint for O-NET, NT, or
  RT — any schema relaxation is scoped to portfolio-type assessments specifically, to avoid
  weakening a constraint the other, working assessment types rely on.

## Cross-References

* [RFC-002](RFC-002-support-rt.md) — the smaller, related "not every assessment has discrete
  items" gap this RFC's analysis builds on.
* [ADR-006](../Architecture-Decision-Record.md#adr-006--why-a-generic-student-centric-assessment-schema) —
  the architectural claim this RFC is the most demanding test of so far.
* [03-Database-Design.md §4](../03-Database-Design.md#4-table-definitions--assessment-framework),
  [§9](../03-Database-Design.md#9-table-definitions--aggregation--materialized-summaries) — the
  tables this RFC's follow-on ADR would need to revisit.
