# Naming Convention

**DMF Learning Analytics Platform (DLAP)**

| | |
|---|---|
| **Document ID** | ONET-DOC-007 |
| **Version** | 1.4.0 |
| **Status** | Frozen — DLAP Documentation Baseline v2.0.0 |
| **Date** | 2026-07-02 |
| **Author** | DMF Platform Team |
| **Related documents** | [00-Project-Overview](00-Project-Overview.md) · [02-System-Architecture](02-System-Architecture.md) · [03-Database-Design](03-Database-Design.md) · [Data-Dictionary](Data-Dictionary.md) |

## Revision History

| Version | Date | Description | Author |
|---|---|---|---|
| 1.0.0 | 2026-07-02 | Initial release. Consolidates naming rules for tables, columns, classes, methods, API actions, and files; formalizes the `DMF\` namespace root and corrects a pre-existing inconsistency in [02-System-Architecture.md](02-System-Architecture.md) where module diagrams used `App\` instead of the documented `DMF\` root. | DMF Platform Team |
| 1.0.1 | 2026-07-02 | QA fix (see [Documentation-QA-Report.md](Documentation-QA-Report.md)): corrected a relative link to `CLAUDE.md` that omitted the `../` needed from within `docs/`; wrapped a literal link-syntax example in code spans so it does not render as a broken clickable link. Frozen as part of the DLAP Documentation Baseline v2.0.0 ([00-Project-Overview.md §13](00-Project-Overview.md#13-documentation-freeze)). | DMF Platform Team |
| 1.1.0 | 2026-07-02 | Post-Freeze Amendment (see [00-Project-Overview.md §13](00-Project-Overview.md#13-documentation-freeze)). Added the root-level `UPPER_SNAKE_CASE.md` convention (for [IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md)) and the `decisions/IDR-NNN-slug.md` convention, in §4 and §6. | DMF Platform Team |
| 1.2.0 | 2026-07-02 | Post-Freeze Amendment. Added the `docs/rfcs/RFC-NNN-slug.md` and living-tracker `UPPER_SNAKE_CASE.md` (§4) conventions, and the RFC heading convention (§6), for the new [docs/rfcs/](rfcs/README.md) and [PROJECT_BOARD.md](../PROJECT_BOARD.md). | DMF Platform Team |
| 1.3.0 | 2026-07-02 | Post-Freeze Amendment. Generalized the `UPPER_SNAKE_CASE.md` rule (§4) from "root-level only" to "operational/actionable documents regardless of location," reframing the distinction as reference-vs-actionable rather than by-directory, after [DECISION_TREE.md](DECISION_TREE.md) was added *under* `docs/` using that casing. Updated the `ONET-DOC-NNN` row (§6) to state explicitly that casing does not determine baseline membership. | DMF Platform Team |
| 1.3.1 | 2026-07-02 | Post-Freeze Amendment. Updated the `ONET-DOC-NNN` row (§6) for [ARCHITECTURE.md](../ARCHITECTURE.md) (`ONET-DOC-014`, project root). | DMF Platform Team |
| 1.4.0 | 2026-07-03 | Post-Freeze Amendment, made during Module 2 implementation. The module-specific environment variable prefix (§5) changed from `ONET_` to `DLAP_` — see [decisions/IDR-006](../decisions/IDR-006-dlap-env-prefix.md). | DMF Platform Team |

## Table of Contents

1. [Database Naming](#1-database-naming)
2. [PHP Naming](#2-php-naming)
3. [API Naming](#3-api-naming)
4. [File & Directory Naming](#4-file--directory-naming)
5. [Configuration & Environment Variables](#5-configuration--environment-variables)
6. [Documentation Naming](#6-documentation-naming)
7. [Worked Example: Resolving a Real Inconsistency](#7-worked-example-resolving-a-real-inconsistency)
8. [Cross-References](#8-cross-references)

---

## 1. Database Naming

Source of truth for every rule below: [03-Database-Design.md §1](03-Database-Design.md#1-design-principles).

| Element | Convention | Example |
|---|---|---|
| Table | `snake_case`, plural | `students`, `student_enrollments`, `question_analysis` |
| Column | `snake_case` | `student_id`, `academic_year`, `percent_correct` |
| Primary key | `id` (surrogate) or `<entity>_id` (natural key) | `id` on `classrooms`; `student_id` on `students` |
| Foreign key | `<referenced_entity_singular>_id` | `classroom_id` (→ `classrooms.id`), `assessment_id` (→ `assessments.id`) |
| Boolean flag | `is_<adjective>`, type `TINYINT(1)` | `is_active`, `is_correct`, `is_current` |
| Timestamp | `<verb>_at`, type `DATETIME` | `created_at`, `updated_at`, `last_computed_at`, `locked_until` |
| Enum-like status | `snake_case` column, lowercase `snake_case` values | `status ENUM('active','transferred','graduated')` |
| Junction/association table | `<table_a_singular>_<table_b_plural>` or a descriptive compound name when a junction carries its own meaning | `teacher_classrooms`, `question_secondary_indicators` |
| Reference/lookup table | Plural noun ending in the concept it enumerates | `assessment_types`, `subjects` |

**Rule, not just pattern:** a column name should be understandable from the column alone, without
needing to read its table name for context — `student_scores.assessment_id` is preferred over a
bare `id` that only makes sense as "the assessment's id" once you already know which table you're
in.

## 2. PHP Naming

Source of truth: [02-System-Architecture.md §4–§5](02-System-Architecture.md#4-layered-architecture),
matching `dmf-core` and `grade.dmf.ac.th`'s established PSR-12 conventions.

| Element | Convention | Example |
|---|---|---|
| Namespace root | `DMF\` (this module) — **not** `App\`, which is `dmf-template`'s unused default. `dmf-core` itself uses `Dmf\Core\`. | `DMF\Import\ImportJob`, `DMF\Student\Enrollment` |
| Class | `PascalCase`, noun | `ImportJob`, `StandardPerformanceRepository`, `TokenManager` |
| Interface | `PascalCase`, suffixed `Interface` | `ConnectionInterface`, `HasherInterface` (per `dmf-core` `Contract\*`) |
| Abstract class | `PascalCase`, no special prefix/suffix — abstractness is a `abstract` keyword fact, not a naming fact | `AbstractRepository`, `AbstractLogger` |
| Method | `camelCase`, verb or verb phrase | `forClassroom()`, `assertNotLocked()`, `fromEnvironment()` |
| Property | `camelCase` | `$classroomId`, `$academicYear` |
| Constant / enum case | `UPPER_SNAKE_CASE` | `MAX_LOGIN_FAIL`, `ONET`, `MID_TEST` (as PHP `enum` cases if the assessment-type set is ever mirrored in code, matching the database `assessment_types.code` values) |
| PHPDoc / type-hint style | Native PHP 8.3 types preferred over PHPDoc where expressive enough; PHPDoc reserved for generics-like array-shape documentation `dmf-core` already uses | `public function find(string $studentId): ?Student` |

**Module namespace segments** follow the module names in
[02-System-Architecture.md §3](02-System-Architecture.md#3-module-decomposition) exactly, in
`PascalCase`: `DMF\Student\*`, `DMF\Import\*`, `DMF\Standards\*`, `DMF\Analytics\*`,
`DMF\Reporting\*`, `DMF\Diagnostics\*`, `DMF\Notification\*`, `DMF\Action\*`, `DMF\Repository\*`.

## 3. API Naming

This module's REST layer does not use conventional path-segment resource routing — it uses
`dmf-core`'s `Http\Router` `"METHOD:action"` dispatch, matching `grade.dmf.ac.th`
(`?action=login_student`, `?action=class_summary`), per
[02-System-Architecture.md §6](02-System-Architecture.md#6-request-lifecycle). This means two
different naming rules apply to two different things, and it is important not to conflate them:

| Element | Convention | Example |
|---|---|---|
| `action` query-parameter value (the *only* routing mechanism v1.0 actually uses) | `snake_case`, verb-first or resource-first to match the existing `grade.dmf.ac.th` vocabulary | `action=login_staff`, `action=import_status`, `action=class_summary` |
| JSON response field | `snake_case`, matching the database column it reflects (SSOT — see [Architecture-Principles.md §1](Architecture-Principles.md#1-single-source-of-truth-ssot)) | `{"student_id": "...", "percent_correct": 92.5}` |
| Future versioned path-based routes (`/api/v1/...`), if ever added per [02-System-Architecture.md §10](02-System-Architecture.md#10-integration-architecture) | `kebab-case` path segments, since these would be conventional REST resource URLs, not `action` dispatch values | `/api/v1/student-scores`, `/api/v1/standard-performance-summary` |
| HTTP header | Standard HTTP `Kebab-Case` (framework/protocol convention, not this project's choice) | `Authorization: Bearer <token>`, `X-Frame-Options` |

**Why the split:** an `?action=` value is not a URL path segment — it is a dispatch key compared
against a PHP `match`/array-lookup, and `grade.dmf.ac.th`'s entire existing action vocabulary
(`login_student`, `login_parent`, `class_list`, `manage_teachers`) is already `snake_case`.
Renaming this module's actions to kebab-case (`login-student`) purely to match a generic "APIs use
kebab-case" rule would create the exact kind of platform-wide inconsistency
[Architecture-Principles.md §8](Architecture-Principles.md#8-backward-compatibility) warns against,
for a URL-shape convention that does not actually apply to a non-path-based dispatch mechanism.
Kebab-case is reserved for the day this module (or the platform) adds real path-based REST routing.

## 4. File & Directory Naming

| Element | Convention | Example |
|---|---|---|
| Directory | lowercase, matching its PSR-4 namespace segment in lowercase | `app/`, `app/student/` maps to nothing directly — see note below |
| PHP class file | `PascalCase.php`, exactly matching the class name it contains (PSR-4) | `ImportJob.php`, `TokenManager.php` |
| Config file | lowercase `snake_case.php` | `database.php`, `auth.php` |
| Migration file | `YYYYMMDD_HHMMSS_description.sql`, `snake_case` description | `20260702_000000_create_core_tables.sql` |
| Test file | `<ClassUnderTest>Test.php`, mirroring the source class name | `ImportJobTest.php` |
| Reference/specification document | `PascalCase-With-Hyphens.md` for standalone docs; `NN-PascalCase-With-Hyphens.md` for the numbered core set — describes *what is true* (a schema, a set of rules, a domain model) | `Naming-Convention.md`; `03-Database-Design.md`; `Domain-Model.md` |
| Operational/actionable document | `UPPER_SNAKE_CASE.md` — describes *what to do*, read repeatedly while doing it, regardless of whether it lives at the project root or under `docs/`. The GitHub convention for top-level meta files (`README.md`, `CONTRIBUTING.md`) generalized to any document with this character, not just root-level ones | `IMPLEMENTATION_GUIDE.md` (root), `PROJECT_BOARD.md` (root), `DECISION_TREE.md` (under `docs/` — a routing *procedure*, not a specification, which is why it breaks the "reference docs live under `docs/`" pattern on purpose); `CLAUDE.md` is the one legacy exception (single word, predates this rule, kept as-is per Backward Compatibility) |
| Decision record file | `decisions/IDR-NNN-kebab-case-slug.md`, zero-padded 3-digit number, one file per decision | `decisions/IDR-001-phpspreadsheet-for-excel-import.md` |
| Change proposal file | `docs/rfcs/RFC-NNN-kebab-case-slug.md`, zero-padded 3-digit number, one file per proposal — its own sequence, parallel to but not the same numbering as IDR | `docs/rfcs/RFC-001-support-nt.md` |
| Living tracker file | `UPPER_SNAKE_CASE.md`, at the project root — same casing rule as the root operational-guide row above, but explicitly *not* part of the frozen `docs/` baseline (see [00-Project-Overview.md §13](00-Project-Overview.md#13-documentation-freeze)) | `PROJECT_BOARD.md` |

**Note on the `app/` directory:** per [dmf-template](https://github.com/dmf-platform/dmf-template)'s
convention (also followed by `grade.dmf.ac.th`), the top-level source directory is the lowercase
folder `app/`, which PSR-4-maps to the `DMF\` namespace root — the directory name and the
namespace name are allowed to differ in case specifically because this is the platform-wide,
already-established pattern (`"DMF\\": "app/"` in `grade.dmf.ac.th`'s `composer.json`). This is a
deliberate exception, not an inconsistency: directory names stay lowercase by filesystem
convention; namespace names stay `PascalCase` by PHP convention.

## 5. Configuration & Environment Variables

| Element | Convention | Example |
|---|---|---|
| Environment variable | `UPPER_SNAKE_CASE`, module-prefixed where ambiguous | `DB_PASS`, `TOKEN_SECRET`, `LLM_API_KEY`, `DLAP_*` (via `Config::fromEnvironment('DLAP_')` — see [02-System-Architecture.md §16](02-System-Architecture.md#16-cross-cutting-concerns)) |
| Config array key (PHP) | `snake_case`, matching the environment variable it reads, lowercased | `'db_pass'`, `'token_secret'` |

The module-specific config prefix is **`DLAP_`**, matching the product name — not `ONET_`, and not
`DMF_ACADEMIC_`. It was renamed from `ONET_` during Module 2 implementation: unlike the
`dmf_academic` database name and the `onet.dmf.ac.th` domain (both genuinely costly to change once
anything depends on them), nothing had ever deployed against the env-var prefix, so there was no
backward-compatibility cost to weigh against matching the actual product name. `DMF_ACADEMIC_` was
considered and rejected as ambiguous — it reads as platform-wide (the whole DMF Platform) rather
than specific to this module. See [decisions/IDR-006](../decisions/IDR-006-dlap-env-prefix.md) for
the full reasoning, and [CLAUDE.md](../CLAUDE.md) for which identifiers are still kept unchanged
and why.

## 6. Documentation Naming

| Element | Convention | Example |
|---|---|---|
| Document ID | `ONET-DOC-NNN`, zero-padded to 3 digits, assigned once and never reused — reserved for documents in the frozen `docs/` baseline **or the project root**, regardless of `UPPER_SNAKE_CASE.md` vs. `PascalCase-With-Hyphens.md` casing (`IMPLEMENTATION_GUIDE.md`, `DECISION_TREE.md`, and `ARCHITECTURE.md` all get one); **not** used for `decisions/`, `docs/rfcs/`, or `PROJECT_BOARD.md`, which are living/growing and explicitly outside the frozen baseline, per [00-Project-Overview.md §13](00-Project-Overview.md#13-documentation-freeze) | `ONET-DOC-000` through `ONET-DOC-014` |
| Section heading anchor | GitHub's default slug algorithm (lowercase, non-word characters stripped, spaces → hyphens) — never hand-picked | `## 5. YAGNI — You Aren't Gonna Need It` → `#5-yagni--you-arent-gonna-need-it` |
| Cross-reference link text | `` [DocumentName.md §N](DocumentName.md#anchor) `` when citing a specific section; `` [DocumentName.md](DocumentName.md) `` when citing a whole document | `[01-PRD.md §6](01-PRD.md#6-scope)` |
| ADR heading | `ADR-NNN — Why <the question this decision answers>?` | `ADR-006 — Why a Generic, Student-Centric Assessment Schema?` |
| IDR heading | `IDR-NNN — <the concrete implementation choice, as a noun phrase, not a question>` | `IDR-002 — Chart.js Integration for the Dashboard Module`; contrast with the ADR row above — an ADR is phrased as the question it answers, an IDR as the choice itself, reflecting that an IDR is a narrower, more settled kind of decision (see [decisions/README.md §1](../decisions/README.md#1-adr-vs-idr)) |
| RFC heading | `RFC-NNN — <the change being proposed, as a short imperative/noun phrase>` | `RFC-003 — Support Portfolio Assessment`; an RFC precedes and may motivate an ADR or IDR — see [docs/rfcs/README.md §1](rfcs/README.md#1-three-tiers-rfc--adr--idr) |

## 7. Worked Example: Resolving a Real Inconsistency

This section exists because a real naming inconsistency was found and fixed while writing this
document, and the fix is a better illustration of *how to apply* these rules than another
hypothetical example would be.

**What was found:** [02-System-Architecture.md §5](02-System-Architecture.md#5-repository--directory-structure)
has always explicitly stated the namespace root is `DMF\`, "not the template's default `App\`, to
stay consistent with the sibling reference implementation." Yet the Module Decomposition diagram
(§3) and the Layered Architecture diagram (§4) used `App\Import\*`, `App\Student\*`,
`App\Action\*`, and similar — the literal namespace the document itself says was rejected. This
predated the DLAP rename; it was carried forward from the original v1.0.0 draft.

**Why it happened:** the diagrams were written by generalizing from `dmf-template`'s own example
code (which does use `App\`, since that project's whole purpose is being a generic starting point
before a consumer renames it), without updating the namespace to match the specific decision this
project's own §5 had already made.

**How it was resolved:** every `App\` occurrence referring to *this project's own code* in
[02-System-Architecture.md](02-System-Architecture.md) was corrected to `DMF\`. The two remaining
`App\` mentions in that document are intentional and correct — they are the sentences explaining
that `App\` is `dmf-template`'s default and was *not* chosen, which is exactly the kind of
reference this naming convention document expects to survive: it names the rejected alternative to
explain the decision, per [Architecture-Decision-Record.md](Architecture-Decision-Record.md)'s own
"Alternatives Considered" format.

**The general lesson:** when copying a diagram, an example, or a code snippet from a template or a
sibling project, the namespace/naming placeholders are exactly the part most likely to be carried
over unchanged by accident, because they are syntactically valid either way — nothing errors,
nothing looks obviously wrong. This is why [Documentation-QA-Report.md](Documentation-QA-Report.md)
explicitly checks Naming Convention as a category, not just Markdown syntax and broken links.

## 8. Cross-References

* Structural rules these naming conventions apply to: [03-Database-Design.md](03-Database-Design.md).
* Business-meaning counterpart to the column names here: [Data-Dictionary.md](Data-Dictionary.md).
* The principle motivating "one convention, not per-module variation": [Architecture-Principles.md §2](Architecture-Principles.md#2-convention-over-configuration).
* Platform conventions inherited without deviation: `grade.dmf.ac.th/CLAUDE.md`, `dmf-core/docs/coding-standards.md`.
