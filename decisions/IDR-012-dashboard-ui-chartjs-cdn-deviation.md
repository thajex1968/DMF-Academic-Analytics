# IDR-012 — Dashboard Presentation Layer: Chart.js Loading, Data Gaps, and Assessment Selection

**Status:** Accepted — 2026-07-04
**Implements:** [ADR-005](../docs/Architecture-Decision-Record.md#adr-005--why-chartjs) (Chart.js
itself); **deviates from** [IDR-002](IDR-002-chartjs-for-dashboard.md) (vendoring) as explained
below; Sprint 5 (Dashboard Presentation Layer).

## Context

[IDR-002](IDR-002-chartjs-for-dashboard.md) already decided Chart.js must be **vendored** — a
version-pinned local copy under `public_html/assets/vendor/chartjs/` — specifically rejecting a
public CDN so the Dashboard keeps working if a school network blocks external hosts. Sprint 5's own
instruction says "Use Chart.js only," without repeating the vendoring requirement, and this
implementation environment has no package manager, no build step, and no way to fetch and verify a
real third-party binary/minified file from a registry — there is nothing here to vendor *from*.
Writing a placeholder file and calling it Chart.js would be worse than being explicit about the gap:
it would silently ship a broken or fabricated dependency.

## Decision

* **Chart.js and Bootstrap 5 are both loaded from a CDN in this Sprint**, exactly matching how
  `public_html/index.html` already loads Bootstrap (Sprint 3, unchanged) — not vendored, **contrary
  to IDR-002**. This is a known, explicit, and reported deviation, not a silent one.
* **Follow-up required before this is production-ready**: someone with the ability to run `npm`/`curl`
  outside this environment should download the pinned Chart.js version, commit it under
  `public_html/assets/vendor/chartjs/`, and swap the `<script src="https://cdn...">` tag in
  `public_html/dashboard/dashboard.html` for the vendored path — at that point IDR-002 is satisfied
  again and this deviation is closed. Tracked as a Known Limitation in the Sprint 5 completion
  report, not fixed here.
* **Chart configuration still follows IDR-002's server-assembled principle**: every number a chart
  draws (percent-correct, mean, benchmark comparison) comes directly from the Dashboard Data API's
  JSON, unmodified by client-side logic; the client only maps API fields onto Chart.js's `data`/
  `options` shape. No threshold or business rule is computed in JavaScript.

## Real data gaps found while building against the frozen Dashboard API (not fixed — API is frozen this Sprint)

Building the requested Cards/Charts against the **actual, existing** Dashboard Data API (Phase 3,
must not change) surfaced real gaps between what Sprint 5 asks the UI to show and what the API
currently exposes:

* **No "Difficulty" figure is exposed anywhere in `DashboardResponse`.** `DifficultyCalculator`
  (Sprint 4 Phase 2) computes per-question difficulty, but Phase 3's `AnalyticsAggregationService`
  never aggregated or surfaced it — there is no `difficulty` field on any Dashboard DTO. The
  "Difficulty" card and the "Difficulty Distribution" chart both render an honest empty state
  ("not available in the current Dashboard API") rather than fabricating a number. Extending the
  API to expose this is a Phase 3 change — out of this Sprint's scope by its own explicit rule.
* **`highest`/`lowest`/`average` on `DashboardSubject` are always `null` today** — Phase 2's
  `SubjectPerformanceCalculator` already documents why (no per-student score series in the Canonical
  Model yet) and returns `null` with a warning, on purpose. The "Highest Score"/"Lowest Score"/
  "Average Score" cards render whatever the API sends — `null` today, a real number the moment a
  future Level 2 source populates the underlying data — never a placeholder value invented client-side.
* **`benchmarks` is always `[]` today** — no Level 1 Assessment Adapter exists yet
  (RFC-004; decisions/IDR-011 §7). The Benchmark Comparison chart and Benchmark Summary card render
  an honest empty state, not a fabricated comparison.

## Assessment selector — matches IDR-011 §2's existing constraint, not a new one

`dmf-core`'s `Request` still has no way to accept a caller-supplied assessment id
(decisions/IDR-011 §2, unchanged this Sprint), so the Dashboard Data API only ever answers for "the
latest assessment." The Phase 4 "Assessment selector" is built as an **indicator**, not a
functioning multi-assessment switcher: it displays the current (only) assessment and is
intentionally non-interactive beyond that, with a visible note explaining why — a disabled `<select>`
with one real option is honest; a dropdown that looks functional but silently does nothing on
selection would not be.

## Alternatives Considered

* **Fabricate a local Chart.js file** (hand-written stub) — rejected outright; a fake third-party
  dependency is strictly worse than a documented, reversible CDN dependency.
* **Skip charts requiring unavailable data (Difficulty Distribution, Benchmark Comparison)
  entirely** — rejected; Sprint 5 explicitly asks for both by name, and an honest empty state (with
  a clear reason shown to the user) is more useful than silently omitting the chart a stakeholder
  was told to expect.
* **Build a working multi-assessment selector against a client-side-only list** — rejected; there is
  exactly one assessment the API can ever return today, so a working selector would have nothing
  real to switch between, and implying otherwise would misrepresent the system's actual capability.

## Consequences

* Before production deployment, vendor Chart.js per IDR-002 and update the one `<script>` tag —
  tracked explicitly, not forgotten.
* When Sprint 4 Phase 3 is ever revisited to expose difficulty/benchmark/per-student figures, the
  Dashboard Presentation Layer already has the empty-state UI in place and only needs its data
  binding updated — no new component required.
* If `dmf-core`'s `Request` ever gains query-parameter support, the assessment "selector" already has
  the right DOM shape to become a real control — its `<select>` just needs enabling and wiring to a
  new request parameter at that time.
