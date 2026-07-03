# IDR-002 — Chart.js Integration for the Dashboard Module

**Status:** Accepted — 2026-07-02
**Implements:** [ADR-005](../docs/Architecture-Decision-Record.md#adr-005--why-chartjs) (the
architecture-level choice of Chart.js itself); Task T3.3
([IMPLEMENTATION_GUIDE.md §2](../IMPLEMENTATION_GUIDE.md#2-task)).

## Context

[ADR-005](../docs/Architecture-Decision-Record.md#adr-005--why-chartjs) already decided *that* the
platform uses Chart.js, and why (lightweight, canvas-based, no server-side chart rendering needed).
It did not decide *how* the Dashboard module (`DMF\Action\Dashboard\*`,
[docs/02-System-Architecture.md §3](../docs/02-System-Architecture.md#3-module-decomposition))
actually wires it up: whether the library is loaded from a public CDN or vendored as static assets,
where chart configuration (colors, thresholds, axis labels) is assembled, and how the one non-core
chart type this platform needs — the classroom×standard heatmap
([docs/01-PRD.md §22](../docs/01-PRD.md#22-dashboard--visualization)) — is rendered, since Chart.js
core has no matrix/heatmap chart type.

## Decision

* **Vendored, not CDN-loaded.** Chart.js and the `chartjs-chart-matrix` plugin are downloaded once
  and committed as static assets under `public_html/assets/vendor/chartjs/`, referenced by a
  version-pinned `<script>` tag — not loaded from a public CDN at runtime.
* **Server-assembled configuration.** Each Dashboard Action handler
  (`DMF\Action\Dashboard\*`) returns chart *data and configuration* as JSON — series values, color
  thresholds, axis labels, the specific `percent_correct` cut-off that colors a heatmap cell red vs.
  green — computed in PHP from the pre-aggregated summary tables
  ([docs/02-System-Architecture.md §8](../docs/02-System-Architecture.md#8-analytics--aggregation-architecture)).
  The client-side JavaScript is a thin renderer: it instantiates a `Chart` object from the JSON it
  receives and does not itself decide what a "weak" standard is.
* **`chartjs-chart-matrix`** is the plugin used for the classroom×standard heatmap
  ([docs/01-PRD.md §22](../docs/01-PRD.md#22-dashboard--visualization)); every other chart type
  (Trend, Radar, Item Analysis, Standard Coverage, Forecast) uses Chart.js's built-in `line`, `bar`,
  `radar`, and `doughnut` types directly.

## Alternatives Considered

* **Public CDN (e.g., cdnjs, jsDelivr)** — rejected. A CDN dependency means the Dashboard silently
  breaks if the CDN is unreachable or blocked by a school network firewall, and shared hosting's
  deploy model (FTP/SSH file upload,
  [docs/02-System-Architecture.md §13](../docs/02-System-Architecture.md#13-deployment-architecture))
  already assumes no runtime dependency on an external service beyond the optional LLM API
  (FR-015). Vendoring costs one manual update step per Chart.js version bump, in exchange for the
  Dashboard working offline-from-third-parties.
* **Client-side threshold/color logic** — rejected. Business rules (what counts as a "weak"
  standard, what color a percentage maps to) belong in the PHP domain layer, matching Single
  Source of Truth ([docs/Architecture-Principles.md
  §1](../docs/Architecture-Principles.md#1-single-source-of-truth-ssot)) — duplicating a threshold
  constant in JavaScript risks it silently drifting from the PHP value used by, e.g., the AI
  Diagnostics module's own threshold check (FR-014).
* **A separate heatmap library instead of `chartjs-chart-matrix`** (e.g., a bespoke `<table>`-based
  heatmap with CSS background colors) — rejected for now. It would avoid the one third-party plugin
  dependency this decision introduces, but at the cost of a second charting approach living
  alongside Chart.js for every other visualization — inconsistent interaction/tooltip behavior
  across the dashboard for a KISS violation with no corresponding benefit
  ([docs/Architecture-Principles.md §6](../docs/Architecture-Principles.md#6-kiss--keep-it-simple)).

## Consequences

The Dashboard module has exactly one third-party JS dependency to track across upgrades
(`chartjs-chart-matrix`, since it must stay compatible with whichever Chart.js major version is
vendored) — already flagged as a consequence of ADR-005 itself. Vendoring means a Chart.js version
bump is a deliberate, reviewed action (replace the vendored file, re-test every chart type), not an
automatic CDN-side update — consistent with Backward Compatibility
([docs/Architecture-Principles.md §8](../docs/Architecture-Principles.md#8-backward-compatibility)):
nothing on the Dashboard changes behavior without someone choosing that change.
