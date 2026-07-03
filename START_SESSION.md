# Start Session

**DMF Learning Analytics Platform (DLAP)**

| | |
|---|---|
| **Document ID** | ONET-DOC-015 |
| **Version** | 1.0.0 |
| **Status** | Frozen — DLAP Documentation Baseline v2.0.0 |
| **Date** | 2026-07-02 |
| **Author** | DMF Platform Team |
| **Related documents** | [CLAUDE.md](CLAUDE.md) · [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) · [PROJECT_BOARD.md](PROJECT_BOARD.md) · [ARCHITECTURE.md](ARCHITECTURE.md) |

## Revision History

| Version | Date | Description | Author |
|---|---|---|---|
| 1.0.0 | 2026-07-02 | Initial release, added as a Post-Freeze Amendment to the DLAP Documentation Baseline v2.0.0 (see [docs/00-Project-Overview.md §13](docs/00-Project-Overview.md#13-documentation-freeze)). The fixed procedure every implementation session starts with. | DMF Platform Team |

## Purpose

This is the procedure — for a human or an AI agent — to run at the start of **every** implementation
session, so that no session ever starts from assumption instead of from the actual current state of
the code, the board, and the decision record. It does not replace
[IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) (which defines *what* each task means) — it is
the fixed on-ramp to it.

---

## The Procedure

### 1. Read, in order

1. [CLAUDE.md](CLAUDE.md) — project identity, tech stack, conventions, current status.
2. [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) — Roadmap, Task breakdown, Implementation
   Order, Coding Rules, Definition of Done, QA Checklist.
3. [PROJECT_BOARD.md](PROJECT_BOARD.md) — the current sprint's live status: what's Todo, In
   Progress, in Review, and Done.

### 2. Review current project status

* Confirm [PROJECT_BOARD.md](PROJECT_BOARD.md)'s sprint header still matches
  [IMPLEMENTATION_GUIDE.md §1 Roadmap](IMPLEMENTATION_GUIDE.md#1-roadmap) — if the board says
  "Sprint 1 – Core Platform" but Phase 1's tasks are all Done, the board is stale; say so rather
  than silently starting Phase 2 work without a Sprint 2 board section to track it in.
* Confirm [docs/Release-Notes.md](docs/Release-Notes.md)'s "Unreleased" section
  ([docs/Release-Notes.md §1](docs/Release-Notes.md#1-unreleased)) still accurately reflects
  nothing-in-progress-ahead-of-plan, or update it if it doesn't.

### 3. Review unfinished tasks

* Read every unchecked item under **Todo** and **In Progress** in
  [PROJECT_BOARD.md](PROJECT_BOARD.md).
* Cross-check each against its task ID in
  [IMPLEMENTATION_GUIDE.md §2 Task](IMPLEMENTATION_GUIDE.md#2-task) — the board tracks status only;
  the task's actual definition of "done" lives there
  ([IMPLEMENTATION_GUIDE.md §6](IMPLEMENTATION_GUIDE.md#6-definition-of-done)), not on the board.

### 4. Review the latest decision records, in this order

1. [docs/rfcs/](docs/rfcs/README.md) — any proposal that changes what's in scope. A newly
   **Approved** RFC changes what Task list applies; a **Proposed** one does not yet.
2. [docs/Architecture-Decision-Record.md](docs/Architecture-Decision-Record.md) — the six
   foundational ADRs, plus anything added under `docs/adr/` since.
3. [decisions/](decisions/README.md) — the latest IDRs, for the concrete library/pattern choices
   already made for the task you're about to pick up.

This order matters: RFC → ADR → IDR is the same dependency direction
[decisions/README.md §1](decisions/README.md#1-adr-vs-idr) and
[ARCHITECTURE.md §4](ARCHITECTURE.md#4-the-documentations-own-architecture) both document — reading
backward (IDR first) risks missing that a lower-level decision was made *because of* a
higher-level one you haven't seen yet.

### 5. Continue implementation from the highest-priority task

The highest-priority task is the first unchecked **Todo** item on
[PROJECT_BOARD.md](PROJECT_BOARD.md), read top to bottom — the board's ordering *is* the priority
ordering; do not re-derive priority from [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md)'s full
task list independently of what the board currently says. If the board's Todo list is empty but the
sprint isn't marked done, stop and ask — do not invent a next task.

### 6. Fixed rules for the whole session

* **Never redesign the project.** If implementing a task reveals the architecture doesn't fit,
  that is a new RFC or ADR ([docs/rfcs/README.md](docs/rfcs/README.md)), not an in-place change.
* **Never modify frozen documentation.** Everything under `docs/` (and the frozen root files —
  [CLAUDE.md](CLAUDE.md), [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md),
  [ARCHITECTURE.md](ARCHITECTURE.md), [DECISION_TREE.md](docs/DECISION_TREE.md)) changes only via a
  new, explicit revision-history entry and version bump, per
  [docs/00-Project-Overview.md §13](docs/00-Project-Overview.md#13-documentation-freeze) — never a
  silent edit. [PROJECT_BOARD.md](PROJECT_BOARD.md) is the one exception: it is a living tracker by
  design and is expected to change every session.
* **Update [PROJECT_BOARD.md](PROJECT_BOARD.md) after every completed task** — move it from Todo/In
  Progress to Done (or Review, if this project's workflow calls for review first), before starting
  the next one.
* **Stop after one task.** Do not chain multiple tasks in one session pass without a checkpoint.
* **Wait for user confirmation** before starting the next task.

## Cross-References

* Task definitions and Definition of Done: [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md).
* Current sprint status: [PROJECT_BOARD.md](PROJECT_BOARD.md).
* Where to record a decision made mid-task: [decisions/README.md](decisions/README.md) (IDR) or
  [docs/rfcs/README.md](docs/rfcs/README.md) (RFC, if it's a scope question, not an implementation
  one).
* "Where do I look" for anything else: [ARCHITECTURE.md](ARCHITECTURE.md).
