# RFCs (Requests For Change)

**DMF Learning Analytics Platform (DLAP)**

This folder holds **RFCs** — proposals to change what this platform does, not how it's built. If
you are unsure whether something belongs here, in an ADR, or in an IDR, read [§1](#1-three-tiers-rfc--adr--idr)
first.

## 1. Three Tiers: RFC → ADR → IDR

| | RFC | ADR | IDR |
|---|---|---|---|
| **Answers** | "Should the platform do this at all?" | "Given we're doing it, what architecture-level approach?" | "Given the architecture, exactly which library/pattern?" |
| **Example** | "Activate NT as a supported assessment type" | "Modular Monolith, not microservices" | "PhpSpreadsheet for `.xlsx` parsing" |
| **Changes** | Product/functional scope — what's in [docs/01-PRD.md §6](../01-PRD.md#6-scope) | Architecture — module boundaries, data flow, platform constraints | A concrete package/version/integration choice |
| **Made** | Whenever a stakeholder proposes extending scope | Before implementation, as part of the architecture baseline (rarely revisited) | During implementation, one per concrete choice |
| **Approved by** | School Director + System Administrator, via the Approval Flow ([docs/01-PRD.md §21](../01-PRD.md#21-core-product-capabilities)) | Recorded in [docs/Architecture-Decision-Record.md](../Architecture-Decision-Record.md) or `docs/adr/` | Recorded in [`decisions/`](../../decisions/README.md) |
| **Numbered** | `RFC-NNN` — its own sequence | `ADR-NNN` (part of `ONET-DOC-004`) | `IDR-NNN` (its own sequence) |
| **Part of the frozen baseline?** | **No** — like `decisions/`, this folder is designed to keep growing; explicitly excluded from the [DLAP Documentation Baseline v2.0.0 manifest](../00-Project-Overview.md#13-documentation-freeze) | Yes | No |

**The direction only ever flows one way in a single proposal's lifecycle:** an approved RFC may
*motivate* a new ADR or IDR (e.g., "RFC-001 was approved, so now we need an ADR for how NT's PDF
layout differs enough from O-NET's to need its own parser strategy" — or it may need no new ADR at
all, if the existing architecture already covers it, as [RFC-001](RFC-001-support-nt.md) and
[RFC-002](RFC-002-support-rt.md) argue). An ADR or IDR never retroactively creates or edits an RFC —
if implementation reveals the approved scope was wrong, that is a *new* RFC, not a quiet edit to the
old one, for the same reason [docs/00-Project-Overview.md
§13](../00-Project-Overview.md#13-documentation-freeze) never silently edits a frozen document.

## 2. Why This Matters for This Project Specifically

[ADR-006](../Architecture-Decision-Record.md#adr-006--why-a-generic-student-centric-assessment-schema)
deliberately built the schema so that activating a new assessment type is "a data and configuration
change, not a redesign." RFCs are where that claim gets tested one proposal at a time: each RFC in
this folder proposes activating (or, for [RFC-003](RFC-003-support-portfolio-assessment.md), adding)
one specific assessment type, and its Impact Analysis section is exactly the place to check whether
ADR-006's promise actually holds for that specific case — or whether it exposes a gap the original
generalization didn't anticipate. When it does — as [RFC-002](RFC-002-support-rt.md) and
[RFC-003](RFC-003-support-portfolio-assessment.md) both did — the gap gets worked through as a
design document before it becomes an ADR: see [DECISION_TREE.md](../DECISION_TREE.md), which
routes any non-multiple-choice evidence to the engine that should handle it.

## 3. Format

```markdown
# RFC-NNN — <Title>

**Status:** Proposed | Approved | Rejected | Deferred
**Author:** <name/role>
**Date:** YYYY-MM-DD

## Summary
## Motivation
## Scope of Change
## Impact Analysis
## Alternatives Considered
## Approval Path
## Out of Scope / Non-Goals
## Cross-References
```

## 4. Naming

`docs/rfcs/RFC-NNN-kebab-case-slug.md` — zero-padded 3-digit number, assigned sequentially, never
reused. See [docs/Naming-Convention.md §4](../Naming-Convention.md#4-file--directory-naming).

## 5. Index

| ID | Title | Status | Proposes | Follow-on ADR scoped by |
|---|---|---|---|---|
| [RFC-001](RFC-001-support-nt.md) | Support NT | Proposed | Activate the already-reserved `NT` assessment type | — (no gap found; fits Question Engine) |
| [RFC-002](RFC-002-support-rt.md) | Support RT | Proposed | Activate the already-reserved `RT` assessment type | [DECISION_TREE.md §5](../DECISION_TREE.md#5-observation--observation-engine) |
| [RFC-003](RFC-003-support-portfolio-assessment.md) | Support Portfolio Assessment | Proposed | Add a **new** `PORTFOLIO_ASSESSMENT` code — not among the eleven types [docs/00-Project-Overview.md §6](../00-Project-Overview.md#6-scope) already reserved | [DECISION_TREE.md §4](../DECISION_TREE.md#4-portfolio--evidence-engine) |

Add a row here in the same change that adds the RFC file — this index is the one place a reader
sees every proposal made so far without opening each file.

## 6. Cross-References

* The scope these RFCs propose changing: [docs/01-PRD.md §6–§7](../01-PRD.md#6-scope).
* The reserved-vs-active assessment type list an RFC moves an entry within (or extends):
  [docs/00-Project-Overview.md §6](../00-Project-Overview.md#6-scope),
  [docs/03-Database-Design.md §4](../03-Database-Design.md#4-table-definitions--assessment-framework).
* Architecture-level decisions, one tier below an approved RFC:
  [docs/Architecture-Decision-Record.md](../Architecture-Decision-Record.md).
* Implementation-level decisions, two tiers below:  [decisions/README.md](../../decisions/README.md).
