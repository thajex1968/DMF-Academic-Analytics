# RFC-002 — Support RT

**Status:** Proposed
**Author:** DMF Platform Team
**Date:** 2026-07-02

> **Update, 2026-07-02:** the follow-on ADR this RFC's Impact Analysis called for now has a
> design-level answer — [DECISION_TREE.md §5](../DECISION_TREE.md#5-observation--observation-engine)
> routes RT's oral-fluency component to a proposed **Observation Engine**. The schema question
> (§7 of that document) is still open and still needs its own ADR before implementation; this
> update only means the *shape* of that ADR is now bounded, not that it's been written.

## Summary

Activate **RT (Reading Test — การประเมินความสามารถด้านการอ่าน)** as a supported assessment type,
administered at ป.1.

## Motivation

Same underlying motivation as [RFC-001](RFC-001-support-nt.md): RT is the earliest standardized
data point in a student's Grade 1–6 history this platform is designed to eventually track
end-to-end ([00-Project-Overview.md §3](../00-Project-Overview.md#3-background)). Reading ability at
ป.1 is also a strong, well-established predictor schools already care about independent of this
platform — surfacing it alongside later O-NET/NT results, on the same per-student timeline, is
squarely what [ADR-006](../Architecture-Decision-Record.md#adr-006--why-a-generic-student-centric-assessment-schema)
was written to eventually enable.

## Scope of Change

Same mechanical steps as [RFC-001 §Scope of Change](RFC-001-support-nt.md#scope-of-change) — set
`assessment_types.is_active = 1` for `RT`, build an RT import template, map items to indicators,
extend standards-catalogue data to ป.1, add RT-specific FRs — **with one structural difference**,
flagged in the Impact Analysis below.

## Impact Analysis

**RT is not a single item-based test in the way O-NET and NT are.** Thailand's RT assesses reading
in two components: a written comprehension component (multiple-choice, item-based — the same shape
`questions`/`student_question_responses` already model) and an **oral reading fluency** component
(a teacher listens to a student read aloud and records a score, e.g., words-correct-per-minute or a
rubric level — there is no "question," no "item," and no `correct_choice` in the O-NET/NT sense).

This is the first real stress-test of the generic-assessment claim that surfaces a gap:

* The written component fits the existing schema with no changes — same as RFC-001's finding for
  NT.
* The oral fluency component does **not** naturally populate `questions` or
  `student_question_responses`, because there are no discrete items to respond to. It *can* still
  populate `student_scores` directly (one score per student per assessment, which the schema
  already supports — `student_scores` does not require `student_question_responses` rows to exist)
  and can still be mapped to a `Learning Standard` at the assessment level rather than the
  question level — but `standard_performance_summary` and `question_analysis`'s current recompute
  logic ([03-Database-Design.md §14](../03-Database-Design.md#14-aggregation-recompute-strategy))
  assumes it is aggregating from `student_question_responses`, which the fluency component will
  never populate.

**Conclusion of this analysis:** activating RT's written component needs no schema change and
minimal aggregation-logic change; activating RT's oral fluency component needs a small, explicit
extension to the Analytics recompute logic to also aggregate directly from `student_scores` when no
`student_question_responses` exist for an assessment. This is a real, if modest, architecture-level
follow-on — likely a short ADR under `docs/adr/`, not just an IDR, since it changes an assumption in
the Analytics module's recompute algorithm, not just a library choice
([decisions/README.md §1](../../decisions/README.md#1-adr-vs-idr)).

## Alternatives Considered

* **Support only RT's written component, treat oral fluency as out of scope** — a legitimate
  fallback if the aggregation-logic extension above is judged too costly for the value delivered,
  but rejected as the *proposed* scope here, because oral fluency is arguably the more
  pedagogically important half of RT, and delivering only the multiple-choice half would
  under-deliver on this RFC's own motivation.
* **Model oral fluency as a single-item "assessment"** (one `question`, one `question_response`,
  `is_correct` repurposed as a pass/fail) — considered and rejected. It would avoid the Analytics
  extension above, but at the cost of forcing a rubric/rate score into an `is_correct` boolean it
  doesn't fit, which would misrepresent the data — a correctness-over-convenience call consistent
  with [Architecture-Principles.md §6](../../docs/Architecture-Principles.md#6-kiss--keep-it-simple):
  the simplest *correct* model, not the simplest model that avoids touching Analytics.

## Approval Path

School Director and System Administrator, per [01-PRD.md
§21](../01-PRD.md#21-core-product-capabilities)'s Approval Flow — **plus** a note to the
implementation team that this RFC's approval should be understood to include approval of the
short follow-on ADR described in [Impact Analysis](#impact-analysis) above, since the two are not
meaningfully separable in practice (RT cannot be "fully" supported without it).

## Out of Scope / Non-Goals

* This RFC does not propose a general-purpose "non-item-based assessment" abstraction ahead of
  need — the Analytics extension above is scoped specifically to "aggregate from `student_scores`
  when no item responses exist," not a speculative rework, per YAGNI
  ([Architecture-Principles.md §7](../../docs/Architecture-Principles.md#7-yagni--you-arent-gonna-need-it)).
  If [RFC-003](RFC-003-support-portfolio-assessment.md) is also approved, its own, likely larger,
  non-item-based scoring needs should be evaluated to see whether they can reuse this same
  extension rather than each assessment type growing its own special case.

## Cross-References

* [RFC-001](RFC-001-support-nt.md) — the equivalent, simpler proposal for NT (no structural gap
  found).
* [RFC-003](RFC-003-support-portfolio-assessment.md) — a proposal with a related, larger version of
  the same "not every assessment has discrete items" gap.
* [03-Database-Design.md §14](../03-Database-Design.md#14-aggregation-recompute-strategy) — the
  recompute logic this RFC's approval would require extending.
