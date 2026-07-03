# IDR-006 — Module-Specific Environment Variable Prefix: `DLAP_`, Not `ONET_`

**Status:** Accepted — 2026-07-03
**Implements:** Reverses part of the naming decision recorded in
[docs/Naming-Convention.md §5](../docs/Naming-Convention.md#5-configuration--environment-variables)
and [docs/CLAUDE.md's Naming note](../CLAUDE.md); made during Module 2 implementation planning, at
the Lead Software Engineer's explicit request, with the instruction to first confirm no external
commitment existed before changing anything.

## Context

The documentation baseline (`docs/Naming-Convention.md`, `CLAUDE.md`, `docs/02-System-Architecture.md
§16`) established `Config::fromEnvironment('ONET_')` as the module-specific configuration
namespace, and explicitly justified keeping it — alongside the `dmf_academic` database name and the
`onet.dmf.ac.th` domain — under the Backward Compatibility principle
([Architecture-Principles.md §8](../docs/Architecture-Principles.md#8-backward-compatibility)),
reasoning that all three "predate the product rename."

That reasoning is sound for the database name and the domain: both are things an external system
could plausibly already depend on once deployed (DNS records, a configured MySQL grant, a hosting
account). It does not actually hold for the env-var prefix specifically. Before making any change,
this project's own governance requires checking for a real dependency, not assuming — the same
principle [00-Project-Overview.md §13](../docs/00-Project-Overview.md#13-documentation-freeze)
applies to frozen documents applies here: change only after confirming what's actually at stake.

**Verification performed:** a full-repository search for `ONET_` found exactly seven files, all of
them documentation or the two code files this same implementation session wrote in Module 1
(`.env.example`, `bootstrap/app.php`). No `.env` file exists anywhere (it is `.gitignore`d and none
was ever created for a real environment). No hosting account, CI pipeline, or deployment script
references `ONET_` — none exist yet; nothing has been deployed. There is, concretely, **nothing
outside this repository that could break.**

## Decision

Rename the module-specific configuration prefix from `ONET_` to **`DLAP_`**, matching the actual
product name (DMF Learning Analytics Platform) used everywhere else in this document set since the
v2.0.0 rename. Updated: `.env.example`, `bootstrap/app.php` (the `Config::fromEnvironment()` call
and its assembled config array's `'dlap'` key, renamed from `'onet'`), and the three documentation
references (`docs/02-System-Architecture.md §16`, `docs/Naming-Convention.md §5`, `CLAUDE.md`'s
Naming note) — each with its own Revision History entry, per this baseline's amendment discipline,
not a silent edit.

**The database name, the domain, and the `ONET-DOC-` document ID prefix are explicitly NOT
changed** by this decision — see [§Consequences](#consequences) for why those remain different
cases.

## Alternatives Considered

* **`DMF_ACADEMIC_`** (matching the database name) — considered and rejected. `DMF` alone reads as
  the whole DMF Platform (`dmf-core`, every sibling `*.dmf.ac.th` portal), not specifically this
  module — a developer skimming `DMF_ACADEMIC_SCHOOL_CODE` could reasonably wonder whether it's
  platform-wide config. `DLAP_` is unambiguous: it names this specific product, nothing else.
* **Keep `ONET_`** — rejected as the default this IDR was asked to justify overriding, per the
  explicit instruction to verify first, then act. Verification found no cost to changing it, and a
  real, if modest, ongoing cost to *not* changing it: every new engineer reading `ONET_SCHOOL_CODE`
  in a `DLAP_`-branded codebase has to learn "this one's different, historical reasons" — a small
  tax paid indefinitely versus a one-time rename paid once, while the rename is still cheap.
* **A compatibility shim (read both `ONET_*` and `DLAP_*`, preferring one)** — rejected. This would
  be backward-compatibility scaffolding for a compatibility need that, per the verification above,
  does not exist. Building it would be the same category of premature complexity YAGNI
  ([Architecture-Principles.md §7](../docs/Architecture-Principles.md#7-yagni--you-arent-gonna-need-it))
  already warns against elsewhere in this project.

## Consequences

* Every current call site is updated in this same change — there is no transition period and none
  is needed, since nothing outside this repository ever read the old prefix.
* The database name (`dmf_academic`), the domain (`onet.dmf.ac.th`), and the `ONET-DOC-` document
  ID prefix remain unchanged, and should be evaluated the same way — by checking for an actual
  external commitment — before any future proposal to rename them, rather than assumed frozen
  forever or assumed free to change. For the domain and database specifically, that commitment
  will become real the moment either is configured on production hosting; this decision does not
  pre-judge that future check.
* If a reader ever finds a lingering `ONET_` reference this change missed, that is a documentation
  or code defect to fix via a normal correction, not evidence this decision was wrong — the
  verification in [§Context](#context) covered every occurrence found at the time this IDR was
  written.
