# Project Board

**DMF Learning Analytics Platform (DLAP)**

This is a living sprint tracker, not a frozen specification — unlike everything under `docs/`, it
is expected to change constantly and is **not** part of the DLAP Documentation Baseline v2.0.0
([docs/00-Project-Overview.md §13](docs/00-Project-Overview.md#13-documentation-freeze)). Each
Todo item links to its task ID in [IMPLEMENTATION_GUIDE.md
§2](IMPLEMENTATION_GUIDE.md#2-task), which is the authoritative description of what the task means
and what "done" requires ([IMPLEMENTATION_GUIDE.md §6](IMPLEMENTATION_GUIDE.md#6-definition-of-done))
— this board tracks status only, it does not redefine the task.

---

# Sprint 1 – Core Platform

Maps to [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) Phase 1 (Foundation), Tasks T1.1–T1.4.

## Todo
- [ ] Database — T1.3 (create the `dmf_academic` schema — [docs/03-Database-Design.md](docs/03-Database-Design.md))
- [ ] Migration — T1.3 (one timestamped file per table group, per [docs/03-Database-Design.md §16](docs/03-Database-Design.md#16-migration-strategy))
- [ ] Seeder — T1.4 (seed `assessment_types`: `ONET` active, ten reserved)

## In Progress

## Review

## Done
- [x] Config — T1.1 (scaffold from `dmf-template`, `DMF\` namespace root) — `composer.json`,
      `.phpcs.xml`, `phpstan.neon`, `phpunit.xml`, `config/{app,database,auth}.php`. Verified:
      `composer install` succeeds (including `dmf/core` via local path repository), `composer
      analyse` (PHPStan level 8) clean, `composer lint` (PSR-12) clean.
- [x] Environment — T1.1/T1.2 (`.env`, secrets via env vars, `dmf/core` wired as a dependency) —
      `.env.example`, `app/Config/EnvironmentLoader.php`, `bootstrap/app.php`. Verified: 6/6
      PHPUnit tests passing (`tests/Unit/Config/EnvironmentLoaderTest.php`), plus a manual
      end-to-end smoke test of `bootstrap/app.php` confirming `.env` values, `config/*.php`
      values, and `Config::fromEnvironment('DLAP_')` (renamed from `ONET_` — see
      [decisions/IDR-006](decisions/IDR-006-dlap-env-prefix.md)) all resolve correctly together. See
      [decisions/IDR-004](decisions/IDR-004-custom-env-loader.md) for the `.env`-loading
      approach.

---

## Backlog (not yet in a sprint)

The rest of [IMPLEMENTATION_GUIDE.md §2](IMPLEMENTATION_GUIDE.md#2-task)'s Phase 1 tasks
(T1.5–T1.7: Student & Enrollment module, Auth, Dashboard shell) and every Phase 2–4 task move onto
a board here once Sprint 1 is done — this file only carries the current and next sprint, not the
whole roadmap; see [docs/00-Project-Overview.md §9](docs/00-Project-Overview.md#9-roadmap) and
[docs/Release-Notes.md](docs/Release-Notes.md) for the full multi-release plan.
