# IDR-011 — Dashboard Data API: Routing, Orchestration, Cache, and Data-Source Mapping

**Status:** Accepted — 2026-07-04
**Implements:** [decisions/IDR-005](IDR-005-database-connection-strategy.md) (`ConnectionFactory` reused
here); [decisions/IDR-010](IDR-010-web-application-foundation.md) (SPA + JSON API + Bearer token
architecture this phase builds on, not deviates from); Sprint 4 Phase 3
([IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md#2-task), Phase 3/T3.3's Dashboard layer) —
Analytics Aggregation, Dashboard DTOs, Dashboard Data API, Dashboard Cache, Dashboard Health.

## Context

The Sprint 4 Phase 3 instruction that triggered this work specified REST-style path routes —
`GET /api/dashboard/overview`, `/api/dashboard/assessment`, `/api/dashboard/subjects`,
`/api/dashboard/benchmark`, `/api/dashboard/health` — each implicitly assuming a query parameter
(e.g., an `assessment_id`) could be read per request. Before writing any Action, this was checked
against what `dmf-core` (already a fixed dependency, per Coding Rule 2 — depend on its contracts,
never reimplement) actually provides, and two real conflicts were found:

* `Dmf\Core\Http\Router` is, by its own docblock, "a lightweight action-based router for
  single-file API endpoints... routes are keyed as `\"METHOD:action\"`" — there is no path-segment
  parser anywhere in it. This is not a gap this project introduced; it is the same routing
  mechanism [IDR-010 §1](IDR-010-web-application-foundation.md#1-architecture-spa--json-api--bearer-token-no-deviation-no-adr-needed)
  already adopted for the whole module, and the same mechanism `docs/02-System-Architecture.md
  §6/§10` document as this platform's only routing convention.
* `Dmf\Core\Http\Request` is `final` and exposes exactly two pieces of caller-supplied data: the
  `?action=` value and, for `POST`/`PUT`/`PATCH` only, a JSON request body. It has **no** method to
  read an arbitrary query-string parameter (e.g., `?assessment_id=3`) on a `GET` request — the same
  "`Request` is `final`, cannot be extended" constraint [IDR-010 §5](IDR-010-web-application-foundation.md#5-no-principal-registry--the-dashboard-handler-re-verifies-its-own-token)
  already ran into for a different reason.

Per the same rule [IDR-010](IDR-010-web-application-foundation.md) already invoked — "if
implementing a task reveals the architecture doesn't fit, resolve it explicitly, do not silently
reimplement or silently drop the requirement" — this IDR records both resolutions plus every other
non-obvious call this phase required.

## Decision

### 1. Routing: `"METHOD:action"` dispatch, not path-based REST routes (no deviation, no ADR needed)

The five endpoints are registered as `GET` actions on the existing `Router`, named to preserve the
resource-oriented intent of the requested paths in `snake_case`
([docs/Naming-Convention.md §3](../docs/Naming-Convention.md#3-api-naming)'s documented style,
matching `dashboard_summary` already in production):

| Requested path | Built as (`?action=`) |
|---|---|
| `GET /api/dashboard/overview` | `dashboard_overview` |
| `GET /api/dashboard/assessment` | `dashboard_assessment` |
| `GET /api/dashboard/subjects` | `dashboard_subjects` |
| `GET /api/dashboard/benchmark` | `dashboard_benchmark` |
| `GET /api/dashboard/health` | `dashboard_health` |

No new router, path parser, or middleware was written to make literal path routing work — that
would mean reimplementing what `dmf-core`'s `Http\Router` deliberately does not provide, which
Coding Rule 2 forbids without a new ADR (this is not architecturally significant enough for one;
see [decisions/README.md §1](README.md#1-adr-vs-idr)).

### 2. No `assessment_id` query parameter — Dashboard endpoints resolve "the latest assessment"

Since `Request` cannot carry an arbitrary `GET` parameter, every Dashboard endpoint reports on
**the most recently registered assessment** (`AssessmentRepository::findLatest()`, new, ordered by
`academic_year DESC, id DESC`) rather than accepting a caller-supplied ID. This is not a workaround
forced only by the missing capability — it also matches how a single-school v1.0 dashboard is
actually used (there is one current cohort/assessment cycle to look at, not an arbitrary historical
one; year-over-year comparison is FR-013, a separate, not-yet-built capability). If a future need
for "show me assessment N specifically" is scoped, it will need its own `Request` extension
decision at that time — not assumed here.

### 3. `AnalyticsAggregationService` is the one class Actions call — it also orchestrates, not just merges

Module 1's brief describes `AnalyticsAggregationService` narrowly ("merge calculator outputs into a
unified Dashboard model"), while the Architecture Rules diagram draws `Actions ↓ Aggregation Service
↓ Analytics Engine ↓ Repository` as four distinct hops. Building a fifth, otherwise-unrequested
class purely to sit between "Aggregation Service" and "Repository" would be inventing an
abstraction nothing else asks for (YAGNI). Instead, `AnalyticsAggregationService` exposes two
public entry points that satisfy both readings at once:

* `aggregate(AnalyticsContext $context, AnalyticsResultInterface[] $results): DashboardResponse` —
  the pure, no-I/O merge Module 1 describes; every one of its own unit tests calls this directly
  with hand-built fixtures, never touching a database.
* `forLatestAssessment(): ?DashboardResponse` — the orchestration entry point every Dashboard Action
  actually calls (satisfying "Actions invoke Aggregation Service" and "no Action may call
  repositories directly" literally). Internally it resolves the latest assessment
  (`AssessmentRepository`), loads its responses (`AnalyticsReadRepository`, new), normalizes them
  (`ItemIndicatorNormalizer`, T2.5, unchanged), builds the Canonical context
  (`AnalyticsContextFactory`, Phase 1, unchanged), runs the five calculators
  (`AnalyticsPipeline`, Phase 2, unchanged), and calls its own `aggregate()` — i.e., it *is* the
  "Analytics Engine ↓ Repository" leg of the diagram, composed inside the one service Actions are
  told to call, optionally reading/writing through the injected `DashboardCacheInterface` around
  the whole computation. Returns `null` only when no assessment exists yet (a fresh install) — no
  exception, since that is an expected state, not an error.

### 4. `DashboardCacheInterface` mirrors `Dmf\Core\Contract\CacheInterface` — it does not replace it

`dmf-core` already ships `Contract\CacheInterface` (`get`/`set`/`delete`/`has`/`clear`/`setMany`/
`getMany`) — exactly the "cache key, TTL, invalidate" shape Module 6 asks for. Rather than either
(a) reusing `Dmf\Core\Contract\CacheInterface` directly, which would not give Dashboard code its
own named contract as explicitly requested, or (b) reinventing cache semantics from scratch, which
would violate Coding Rule 2, `DMF\Analytics\Cache\DashboardCacheInterface extends
\Dmf\Core\Contract\CacheInterface` — a zero-new-method marker interface, so a Dashboard-scoped type
hint exists (as asked) while every method is inherited, not reinvented. `InMemoryDashboardCache`
(the only implementation — "memory implementation only, no Redis") stores `{value, expiresAt}`
pairs in a private array, checking `time() >= expiresAt` on read; nothing else was built, matching
`docs/02-System-Architecture.md §16`'s existing "no Redis/Memcached on shared hosting" constraint,
consistent with the file-based cache that section already anticipates for the standards catalogue.
Every consumer of the cache takes it as an **optional** (`?DashboardCacheInterface`, nullable,
default `null`) constructor argument — with no cache injected, every call is a cache miss and the
Dashboard still computes and returns a correct result, satisfying "Dashboard API should work even
when cache disabled" without a separate no-op implementation.

### 5. `BenchmarkRepository` / `DashboardRepository` were not built

Module 5 lists these as *examples*, not a mandate ("if required, create..."). No table anywhere in
`database/schema.sql` stores an externally-published benchmark comparison figure — RFC-004's own
Analysis §1/§4 is explicit that no Level 1 Assessment Adapter exists yet to produce one, and this
phase is expressly forbidden from any database/schema change. Building a `BenchmarkRepository`
against a table that does not exist, or inventing one, would repeat the exact mistake this
project has repeatedly and deliberately declined to make (T1.4's seeder, T2.2's example templates)
— fabricating data or structure no real source has supplied. `AnalyticsReadRepository` (new) is the
one genuinely required addition: it reads `student_question_responses` joined to `questions` by
`assessment_id`, the one real read Phase 3 needs that no existing repository already provided.

### 6. `AnalyticsReadRepository` is read-only by a thrown exception, not just by convention

Every other repository in this codebase satisfies `AbstractRepository`'s abstract
`create()`/`update()`/`delete()` with a real (if sometimes-unused) implementation. Module 5's
"Repositories must remain read-only" is stated as a harder rule than any prior task's "this method
exists but normal flow never calls it" precedent (e.g. `StudentScoreRepository`), so
`AnalyticsReadRepository::create()`/`update()`/`delete()` each throw `\LogicException` immediately
— a genuine write attempt fails loudly rather than silently succeeding against a table this
repository was never meant to mutate.

### 7. What the Dashboard API reports today, honestly

No Level 2 Assessment Adapter exists yet ([RFC-004](../docs/rfcs/RFC-004-multi-source-analytics-architecture.md)),
so `student_question_responses` has no writer anywhere in this codebase — it is empty in any real
deployment right now. `AnalyticsReadRepository::findResponsesForAssessment()` therefore returns
`[]` for the latest real assessment, `NormalizationResult`/`AnalyticsContext` end up all-zero, and
every calculator correctly reports "no data" (empty records, no warnings — Phase 2's calculators
already treat zero responses as a valid, not-erroneous state). The Dashboard Data API is wired
end-to-end for real against real repositories; it reports empty/zero results today because that is
the honest state of the data, not because anything is stubbed. It will begin reporting real figures
the moment any future Level 2 Assessment Adapter commits rows to `student_question_responses` —
no Dashboard code changes when that day comes.

## Alternatives Considered

* **A new path-based router/middleware layered in front of `Http\Router`** — rejected; reimplements
  what `dmf-core` deliberately does not provide, needing an architecture-level decision this
  phase's own scope does not ask for.
* **Accepting `assessment_id` via the POST body on a `GET`-shaped conceptual action** — rejected;
  would abuse the one input channel `Request` does expose for `GET` requests it was never meant
  for, and still would not match the literal `GET` verb requested.
* **A separate "Dashboard Orchestrator" class between `AnalyticsAggregationService` and the
  repositories** — rejected as an unrequested extra abstraction; see §3.
* **Reusing `Dmf\Core\Contract\CacheInterface` directly with no Dashboard-specific interface** —
  rejected only because Module 6 explicitly asked for a named `DashboardCacheInterface`; the
  extension (§4) costs nothing extra in implementation.
* **Building `BenchmarkRepository`/`DashboardRepository` against a not-yet-existing table** —
  rejected; see §5.

## Consequences

* A future path-based REST API (if ever requested) needs its own architecture-level decision — this
  IDR does not preclude one, it only records why this phase did not build one.
* A future "compare a specific historical assessment" feature needs a `Request` capability this
  phase's `Request` does not have (reading an arbitrary `GET` parameter) — that is a `dmf-core`-level
  change, out of this project's control to make unilaterally, and out of this phase's scope.
* When a real Level 2 Assessment Adapter is eventually built, `AnalyticsReadRepository` is already
  the correct read path for it to populate through Normalization — no Dashboard-side change is
  anticipated at that point, per §7.
