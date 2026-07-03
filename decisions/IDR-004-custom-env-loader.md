# IDR-004 — Custom, Dependency-Free .env Loader

**Status:** Accepted — 2026-07-02
**Implements:** No prior ADR named a specific `.env`-loading mechanism; this is the first
decision at this level of detail for Task T1.1/T1.2
([IMPLEMENTATION_GUIDE.md §2](../IMPLEMENTATION_GUIDE.md#2-task)).

> **Note, 2026-07-03:** this record's examples below still say `ONET_` — accurate at the time this
> IDR was written. The prefix was renamed to `DLAP_` in [IDR-006](IDR-006-dlap-env-prefix.md); the
> loader mechanism and reasoning described here are unaffected and remain current.

## Context

`dmf-core`'s `Config\Config::fromEnvironment(string $prefix)` reads from PHP's `$_ENV`
superglobal (confirmed by reading `dmf-core/src/Config/Config.php` directly, not assumed);
`dmf-template`'s `config/database.php` (and now this project's `config/*.php`, per
[03-Database-Design.md §1](../docs/03-Database-Design.md#1-design-principles)'s naming
discipline) read via `getenv()`. Both mechanisms need to be populated from a local `.env` file
during development; in production, DirectAdmin/cPanel already sets real process environment
variables, so nothing needs to populate anything there. No prior document picked a specific
`.env`-parsing mechanism to satisfy both read paths.

## Decision

Write a small, dependency-free `DMF\Config\EnvironmentLoader` class
(`app/Config/EnvironmentLoader.php`) that parses a `.env` file and sets `putenv()`, `$_ENV`,
**and** `$_SERVER` together for each variable, so both consumption patterns already established
in this codebase work without either one needing to change. An already-set variable (real host
environment, or already-loaded) is never overwritten.

## Alternatives Considered

* **`vlucas/phpdotenv`** — rejected. It is the standard choice and would work, but it is a new
  Composer dependency for roughly 70 lines of parsing logic this project's actual `.env` format
  needs (`KEY=VALUE`, `#` comments, optional quoting — no nested variable interpolation, no
  multiline values, none of which this project's env vars use). Per
  [Architecture-Principles.md §6](../docs/Architecture-Principles.md#6-kiss--keep-it-simple)
  (KISS) and [§7](../docs/Architecture-Principles.md#7-yagni--you-arent-gonna-need-it) (YAGNI):
  pulling in a general-purpose parser to cover cases this project doesn't have is the more
  complex choice, not the simpler one, once the actual requirement is this narrow.
* **Rely on `getenv()` only, ignore `$_ENV`** — rejected. It would work for `config/*.php` but
  silently break `Dmf\Core\Config\Config::fromEnvironment('ONET_')`, which is the exact
  documented pattern [02-System-Architecture.md
  §16](../docs/02-System-Architecture.md#16-cross-cutting-concerns) specifies for this project's
  own module-specific configuration. Verified by reading `dmf-core`'s actual `Config` source
  rather than assuming its behavior.
* **No `.env` support at all — require real environment variables everywhere, including local
  development** — rejected as the default. Technically simplest, but it would mean local
  development has no low-friction way to set `DB_PASS`, `TOKEN_SECRET`, etc. without exporting
  shell variables by hand every session, which is worse developer ergonomics for a marginal
  simplicity gain the small loader above already captures at low cost.

## Consequences

One new class, fully covered by `tests/Unit/Config/EnvironmentLoaderTest.php` (quoting,
comments, malformed lines, missing-file no-op, and — the behavior most worth a regression test —
that an already-set variable is never overwritten, which is what keeps a committed `.env.example`
or a developer's local `.env` from ever being able to shadow a production secret). No new
Composer dependency. If a future requirement needs variable interpolation or multiline values
this loader doesn't support, that is a new IDR evaluating `vlucas/phpdotenv` at that point, not a
speculative addition now.
