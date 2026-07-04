# IDR-013 — AI Foundation Layer: Prompt Shape, Provider Boundary, and the Insufficient-Data Guard

**Status:** Accepted — 2026-07-04
**Implements:** Sprint 6 Phase 1 (AI Foundation) — no ADR exists yet for "why an AI layer at all"
(that remains PRD FR-014/FR-015 and ADR-level territory for a future RFC); this IDR only records
the concrete shape this phase's implementation took, per that phase's own instruction to document
any real architecture decision.

## Context

The instruction named four DTOs under `app/AI/DTO/` (`AIInsight`, `AIRecommendation`, `AIResponse`,
`AIContext`) and, separately, said `PromptBuilder`'s "Output" is a "Prompt object" — without listing
a `Prompt` class anywhere in the `CREATE` file list, and without saying whether
`AIProviderInterface::generate()` returns that Prompt's *answer* already shaped as `AIInsight`/
`AIRecommendation`, or something more generic. Building against the instruction literally where it
was silent required real choices; they are recorded here rather than assumed.

## Decision

### 1. `Prompt` lives under `app/AI/Prompt/`, not `app/AI/DTO/`

The four explicitly-named DTOs describe *domain* concepts (an insight, a recommendation, a raw
provider response, a request context). `Prompt` is a different kind of object — the input to a
provider, not an output the rest of the system reasons about — and it is built and consumed
entirely within the `Prompt/` concern (`DashboardPromptBuilder` constructs it,
`AIProviderInterface::generate()` consumes it). Colocating it with the builder that owns its shape,
rather than filing it under the literal `DTO/` list, was judged clearer than forcing every prompt
concept into a folder named for the four already-named DTOs.

### 2. `AIProviderInterface::generate()` returns a generic `AIResponse`, not an `AIInsight`/`AIRecommendation` directly

A `Prompt` carries a `PromptType` (`INSIGHT` or `RECOMMENDATION`); the provider always returns the
same `AIResponse` shape (provider, model, latency, token usage, raw response text) regardless of
which type was requested. Parsing that raw text into the specific structured DTO is
`InsightEngine`'s/`RecommendationEngine`'s job, not the provider's — this keeps
`AIProviderInterface` a true transport boundary ("never expose HTTP implementation," per this
phase's own instruction) and keeps response-shape knowledge in the domain layer, where a real
provider swap (Sprint 6 Phase 2) changes nothing about how responses are interpreted.

### 3. `PromptBuilderInterface`/`InsightGeneratorInterface`/`RecommendationGeneratorInterface` are typed directly against the three Dashboard DTOs, not a generic bag

Exactly one concrete builder (`DashboardPromptBuilder`) exists this phase, and its three named
inputs (`DashboardSummary`, `DashboardHealth`, an "Assessment Summary DTO" — `DashboardAssessment`)
are already Analytics DTOs. A looser signature (e.g. `array $analyticsData`) would have been more
"generic" but would also violate the Prompt Rules ("never pass... raw arrays") and would be
speculative — YAGNI — until a second, differently-shaped builder is actually requested. If Phase 2
or a later phase needs a second prompt shape, that is the point to revisit this interface, not now.

### 4. `PromptType` threads through `Prompt` so one builder and one provider serve both Insight and Recommendation

Rather than two near-duplicate builder interfaces/methods, `PromptBuilderInterface::build()` takes a
`PromptType` and produces a `Prompt` carrying it; `MockProvider` (and any future real provider)
branches its response shape on `$prompt->type`. This keeps `DashboardPromptBuilder` a single class
instead of two copies differing only in their "Required Output" section.

### 5. The "never fabricate, say 'Insufficient data'" safety rule is enforced in PHP, not only in the prompt text

Both `InsightEngine` and `RecommendationEngine` check `$assessment->percentCorrect === null` — the
same litmus test Sprint 4/5 already established for "does this assessment have any computable
data" — **before** building a prompt or calling the provider at all, returning a canned
"Insufficient data." result immediately. The prompt's own Safety Rules section also tells the
provider not to fabricate, but a real LLM is not guaranteed to follow an instruction; enforcing the
rule in PHP for the one case this codebase can detect structurally (no data at all) means the
guarantee does not depend on provider compliance for that case. Partial-data judgment calls (e.g.
"this figure is present but the sample is small") are left to the provider/prompt, since Sprint 6
Phase 1 has no other structural signal to check against.

### 6. `max_tokens` (from `config/ai.php`) is approximated as a caller-supplied character budget, not tokenized here

This codebase has no tokenizer dependency, and adding one would be a new dependency this phase does
not ask for. `InsightEngine`/`RecommendationEngine` accept a plain `int $maxPromptCharacters`
constructor argument instead of reading `config/ai.php` themselves — converting `max_tokens` to a
character estimate (a rough per-provider/per-language heuristic a tokenizer-less codebase cannot do
precisely) is left to whatever wires these engines together (Sprint 6 Phase 2 or later), keeping the
engines themselves config-agnostic and simple to unit test with an exact, arbitrary limit.

### 7. `AIRecommendationPriority` is a new enum, not a plain string

The instruction described `AIRecommendation.priority` as a plain field. Matching this codebase's
established pattern for closed-vocabulary DTO fields (`DashboardAlertLevel`, `CalculatorPriority`,
`BenchmarkScope`), `priority` is a backed enum (`high`/`medium`/`low`) instead of an unchecked
free-text string — a minimal, precedent-matching addition, not a scope expansion.

## Alternatives Considered

* **`AIProviderInterface::generate()` returns `AIInsight|AIRecommendation` directly** — rejected;
  would force the provider boundary to know about domain-specific response shapes, contradicting
  "never expose HTTP implementation" and coupling a future real provider's parsing logic to two
  domain types instead of one generic response envelope.
* **A generic `array $analyticsData` prompt-builder signature** — rejected; violates the Prompt
  Rules directly and is premature generalization for a single existing builder (see §3).
* **Two separate builder interfaces (`InsightPromptBuilderInterface`/`RecommendationPromptBuilderInterface`)** —
  rejected in favor of one interface plus a `PromptType` parameter; avoids near-duplicate classes
  differing only in one prompt section.
* **Reading `config/ai.php` directly inside the engines** — rejected; couples the engines to a
  specific config array shape and complicates unit testing (every test would need a config fixture
  instead of a plain integer).

## Consequences

* Sprint 6 Phase 2 (a real provider) implements `AIProviderInterface` exactly as `MockProvider` does
  — no interface change anticipated, only a new class.
* If a second prompt-consuming DTO shape is ever needed (e.g. a non-Dashboard analytics context),
  `PromptBuilderInterface`'s signature is the first thing to revisit — not assumed to already
  support it.
* The token→character conversion heuristic (§6) will need real tuning once a real provider with a
  real tokenizer/pricing model exists; tracked as a Phase 2 concern, not resolved here.
