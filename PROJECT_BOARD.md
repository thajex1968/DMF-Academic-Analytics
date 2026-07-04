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

Maps to [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) Phase 1 (Foundation), Tasks T1.1–T1.5.

## Todo

## In Progress

## Review

## Done
- [x] Database — T1.3 (create the `dmf_academic` schema — [docs/03-Database-Design.md](docs/03-Database-Design.md)) —
      **Approved.** `database/schema.sql` written: all 28 tables (27 domain + `schema_migrations`), matching
      §3–§10's column definitions, §11 relationships (FKs), §12 indexes, and §13's score-range
      `CHECK` constraint, in FK-dependency (topological) order. Reviewed table-by-table against
      §3–§13, `Data-Dictionary.md`, and `Naming-Convention.md` — no real issue found. Confirmed by
      the direct table-by-table review together with strong indirect evidence (the migrations,
      proven to define an identical 28-table set, ran and passed against a real database). Quality
      gates passed, documentation reconciled, no outstanding architecture issues remain, review
      complete.
- [x] Student & Enrollment — T1.5 (`DMF\Repository\*` for `students`, `student_enrollments`,
      `classrooms`, `teacher_classrooms` — [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task))
      — **Approved.** Scoped down per instruction: only the four repositories, not the "resolve
      current classroom" service T1.5 also names, and not Import Engine (T2.x). Four classes under
      `app/Repository/`, each extending the real `Dmf\Core\Database\Repository\AbstractRepository`
      (read from the actual installed `dmf-core` dependency, not assumed): `StudentRepository`,
      `StudentEnrollmentRepository`, `ClassroomRepository`, `TeacherClassroomRepository`. Each
      implements `table()`/`primaryKey()`/`create()`/`update()`/`delete()` plus a small number of
      pure data-access finder methods justified directly by the schema's own indexes/unique keys
      (e.g. `findCurrentForStudent()`, `findCurrentByStaffUser()`) — no cross-table orchestration
      (e.g. keeping `students.classroom_id` in sync with a new enrollment) is done inside a
      repository; that's deliberately left to the not-yet-built service layer, documented inline.
      **Verified for real**: `vendor/bin/phpunit tests/Unit/Repository` — 25/25 tests, 91
      assertions, all passing (`tests/Unit/Repository/*RepositoryTest.php`, one per class, mocking
      the real `ConnectionInterface`); `vendor/bin/phpstan analyse` — `[OK] No errors` at level 8;
      `vendor/bin/phpcs --standard=.phpcs.xml` — 0 violations. Also added
      `database/verify/001_verify_assessment_types.sql` and `002_verify_subjects.sql` (read-only
      PASS/FAIL SELECT checks against the T1.4 seed data, same plain-SQL pattern as
      migrations/seeders — no new tooling) — ran both for real, all 6 checks PASS.
- [x] Seeder — T1.4 (seed v1.0 foundational master data — [docs/03-Database-Design.md](docs/03-Database-Design.md)) —
      **Approved.** `database/seeders/001_assessment_types.sql` (1 row: `ONET` only — **not** the
      ten reserved codes; `IMPLEMENTATION_GUIDE.md` T1.4's line said otherwise, contradicting
      `01-PRD.md` §6/§25 and `03-Database-Design.md` §4 — found and fixed, see that file's v1.0.1
      revision entry) and `002_subjects.sql` (4 rows: `THAI`/`MATH`/`SCI`/`ENG`, per `01-PRD.md`
      §6's exact v1.0 subject list). `learning_strands`/`learning_standards`/`learning_indicators`
      **deliberately not seeded** — `01-PRD.md` §15/§21 are explicit that the real
      สาระ/มาตรฐาน/ตัวชี้วัด catalogue is entered via the Approval Flow by an academic editor +
      System Administrator, not developer-seeded reference data; no real curriculum source was
      available, and fabricating plausible-looking codes would be actively harmful. New pattern:
      [decisions/IDR-007](decisions/IDR-007-idempotent-sql-seed-files.md) (idempotent raw SQL,
      mirroring migrations — no `Seeder`/`SeederEngine` PHP classes exist yet to run anything
      else). **Verified for real** against `dmf_academic_migration_test` (repurposed as the dev
      database, per instruction — first cleared of the ad-hoc rows inserted during the T1.3
      migration test): ran both files twice each, row counts stable at 1 and 4 (idempotency
      confirmed). **Bug found and fixed during this verification:** the `mysql` CLI's default
      connection charset mis-transcoded the Thai text on first insert (`HEX()` comparison showed
      corrupted bytes, not just a terminal display issue) — fixed with
      `--default-character-set=utf8mb4`, re-verified byte-exact via `HEX()` against known-correct
      UTF-8, and documented as a comment in both seed files so it doesn't recur.
- [x] Migration — T1.3 (one timestamped file per table group, per [docs/03-Database-Design.md §16](docs/03-Database-Design.md#16-migration-strategy)) —
      9 files under `database/migrations/`, `20260703_000001`–`_000009`, one per §3–§10/§16 table
      group, filenames corrected to the frozen `YYYYMMDD_HHMMSS_description.sql` convention
      (`Naming-Convention.md §4`). `subjects` (§5) relocated into `_000001` (Organizational) to
      resolve a real FK-ordering conflict: `assessments` (`_000002`) references `subjects`, which
      would otherwise not exist yet at that point in the sequence. **Verified for real**: started
      MariaDB (XAMPP, `D:\xampp\mysql`, v10.4.32), created a fresh, dedicated
      `dmf_academic_migration_test` database (touched by nothing but these 9 files — never
      `schema.sql`), ran all 9 migrations in filename order with no `CREATE TABLE IF NOT EXISTS`,
      all succeeded. `SELECT COUNT(*) FROM information_schema.tables WHERE
      table_schema='dmf_academic_migration_test'` returned `28`; 40 FK constraints created; table
      names diffed identical to `schema.sql`'s set. Functionally confirmed, not just structurally:
      an FK violation (`import_jobs.uploaded_by` → nonexistent `staff_users` row) was correctly
      rejected, and the `student_scores` score-range `CHECK` correctly rejected `150.00` and
      accepted `87.50`. Test database and MariaDB service left running per instruction (available
      for inspection via phpMyAdmin).
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

# Sprint 2 – Import & Validation

Maps to [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) Phase 2, Tasks T2.1–T2.7 — every task in
this phase is now built and Approved (T2.5–T2.7 approved via GitHub PR review — PR #2
`feature/persistence-layer` for T2.5/T2.6, PR #3 `feature/cron-runner` for T2.7 — reconciled here
to match). Per `IMPLEMENTATION_GUIDE.md` v1.1.0 (see its own Revision History), T2.4 is now "Import
Session & Error Reporting" — the task the entry below was originally reviewed under before the
frozen doc was amended to match it; Normalization (FR-009) moved to T2.5. Duplicate Detection +
Audit Trail (FR-007/FR-008) moved to T2.6. Sprint 2 is complete.

## Todo

## In Progress

## Review

## Done
- [x] Normalization — T2.5 (Item-to-Indicator Normalization, FR-009 —
      [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task)) — **Approved** (merged via PR #2,
      `feature/persistence-layer`, commit `0d35286`). Given directly by instruction.
      `ItemIndicatorNormalizer`, `StandardMappingService`, `QuestionStandardResolver`,
      `NormalizationResult`. Pure in-memory translation step
      ([docs/Business-Flow.md §4](docs/Business-Flow.md#4-normalization)): takes already-imported
      `student_question_responses`-shaped rows and resolves each question to its full
      สาระ→มาตรฐาน→ตัวชี้วัด chain — **writes nothing to the database**; `standard_performance_summary`
      recompute (Analytics, T3.2) and `student_standard_mastery` (explicitly out of scope per
      `IMPLEMENTATION_GUIDE.md` Phase 3) are both future consumers of this output, not built here.
      No mastery/average/difficulty/discrimination calculation, no dashboard update, no AI
      recommendation, no modification of imported scores — normalization only, as scoped.
      - **Analytics layer** (`app/Analytics/Normalization/`, new — first code under `app/Analytics/`):
        `QuestionStandardResolver` (four sequential `findById()` hops — question → primary indicator
        → standard → strand, plus secondary-indicator links — no ORM join, per
        [decisions/IDR-003](decisions/IDR-003-pdo-for-database-layer.md); never fabricates a missing
        link, raises `UnresolvedMappingException` naming the exact id that could not be found),
        `StandardMappingService` (composes one question's primary + de-duplicated secondary
        indicators into a `NormalizedStandardMapping` — a secondary link pointing at the same
        indicator as the primary is dropped, never double-counted: the "duplicate indicator
        protection" requirement), `ItemIndicatorNormalizer` (iterates a batch of responses, caching
        each question's resolution so a question answered by many students in one batch is only
        resolved once — verified by two dedicated cache tests; an unresolvable mapping or an
        invalid/missing `question_id` becomes a traced `UnresolvedMapping` — row number, question
        id, reason — never a fatal exception, so one bad row doesn't abort the whole batch),
        `NormalizationResult`/`NormalizedRecord`/`NormalizedStandardMapping`/`ResolvedIndicator`/
        `ResolvedStandard`/`ResolvedStrand`/`UnresolvedMapping`/`UnresolvedMappingException` — pure
        value objects and one exception type, no behavior beyond what's listed above.
      - **Design decision, not in the instruction's scope list, flagged here rather than assumed**:
        docs/Business-Flow.md §4 states an unresolved mapping "blocks the entire batch from
        proceeding to Storage." This layer does not itself enforce that — `NormalizationResult`
        reports both `records` and `unresolvedMappings` side by side, and a future caller (a
        Storage-stage service, not yet built) is expected to decide whether a batch with any
        unresolved mapping may proceed. Nothing in this task's scope asked for that caller to be
        built, and building it without being asked would be scope creep beyond "normalization only."
      - **Data layer** (`app/Repository/`, five new, all pure CRUD, no extra finder methods beyond
        the inherited `findById()` except where a table's own shape requires one):
        `QuestionRepository`, `QuestionSecondaryIndicatorRepository` (`findByQuestion()`),
        `LearningIndicatorRepository`, `LearningStandardRepository`, `LearningStrandRepository`.
        None of these five tables carry `created_at`/`updated_at` columns (verified against
        `database/schema.sql`), so `update()` does not append one, unlike most other repositories in
        this codebase.
      - **Normalization Golden Dataset** (`tests/fixtures/normalization/`, `NormalizationFixtures.php`
        — entirely synthetic, not real ตัวชี้วัด curriculum content, same discipline as T1.4's seeder
        and T2.2/T2.3's example templates): a 2-strand/2-standard/3-indicator catalogue and 5
        questions, each engineered for one scenario — primary-only, primary+secondary (same
        standard), primary+secondary (different standard), unresolvable primary indicator, and
        duplicate-indicator protection (secondary === primary) — plus 6 response rows exercising
        each scenario and an invalid `question_id`.
      - **Verified for real**: `vendor/bin/phpunit` — **182/182 tests, 520 assertions**, all passing
        (33 new tests: 12 repository tests, `QuestionStandardResolverTest`,
        `StandardMappingServiceTest`, and an `ItemIndicatorNormalizerTest` covering all eight
        required scenarios — only primary indicator, primary+secondary, missing mapping, multiple
        standards, unresolved mapping, duplicate indicator protection, invalid question id, empty
        response set). `vendor/bin/phpstan analyse` — `[OK] No errors` at level 8. `vendor/bin/phpcs
        --standard=.phpcs.xml` — 0 violations across 99 files. `composer dump-autoload` refreshed
        cleanly (same pre-existing, unrelated PSR-4 casing note as T2.3's fixtures directory —
        `tests/fixtures/normalization/NormalizationFixtures.php` is still classmap-loadable).
- [x] Cron Runner + Commit Transaction — T2.7 (FR-006's "no partial commits" rule —
      [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task)) — **Approved** (merged via PR #3,
      `feature/cron-runner`, commit `e4da523`, merge `080789e`). The commit-transaction half was
      already built in T2.3 (`ImportTransactionService`); this task's remaining scope was the
      cron-polled runner itself.
      - **Two genuine gaps investigated and confirmed with you before building**
        ([decisions/IDR-009](decisions/IDR-009-cron-import-runner.md)): (1) nothing resolved which
        `ImportTemplate` applies to a queued job — `ScoreImportService`'s own docblock explicitly
        disclaims guessing this, and every existing caller (all tests) hardcodes one; (2) no CLI/cron
        entry-point convention exists anywhere in the DMF Platform family (`dmf-core`, this project,
        or `../grade.dmf.ac.th`) — confirmed by reading `grade.dmf.ac.th`'s actual
        `docs/OPERATIONS.md`/`docs/PRODUCTION.md`, whose cron-invoked PHP is a plain, non-shebang
        script under `public_html/api/system/`, not a `bin/` directory. Resolved per your direction:
        an injectable `TemplateResolver` defaulting to the one example template (swappable later
        without touching any caller), and a plain script matching `grade.dmf.ac.th`'s convention.
      - **Cron layer** (`app/Import/Cron/`, new): `ImportJobRunner` (polls a bounded batch —
        `$maxJobsPerRun`, default 10, per PRD §20's 30-second-per-file NFR and the "no long-running
        workers outside cron" hosting constraint — of queued jobs, oldest first, and runs each
        through the already-approved `ImportSessionService`; a `Throwable` outside
        `ScoreImportService`'s own handling — most likely template resolution — is caught per job,
        the job is marked `failed` with the same safe generic message convention as T2.3/T2.6, and
        the runner moves on rather than aborting the whole tick), `JobOutcome`/`RunSummary` (plain
        outcome DTOs — processed/success/failure counts).
      - **Template layer** (`app/Import/Template/TemplateResolver.php`, new): `resolveForAssessment(int
        $assessmentId): ImportTemplate` — v1.0 always returns the one configured default template;
        accepts `$assessmentId` now so a future real per-academic-year lookup only changes this
        method's body, not any caller.
      - **Database layer** (`app/Database/ConnectionFactory.php`, new): completes
        [decisions/IDR-005](decisions/IDR-005-database-connection-strategy.md) — designed in Module 1
        planning but never built until this task actually needed a real `Connection` outside a test
        double. `TransactionManager` (IDR-005's other half) remains deliberately deferred — this
        pipeline is still one top-level transaction per commit, so the nesting-guard it would provide
        still has no real trigger.
      - **Repository fix**: `ImportJobRepository::findQueued()` previously delegated to the inherited,
        unordered `findWhere()` — rewritten as its own SQL with `ORDER BY created_at ASC, id ASC` so
        the runner's FIFO processing is deterministic, not incidental.
      - **Entry point** (`public_html/api/cron/import_runner.php`, new — the first file under
        `public_html/` this project has ever created): a plain, non-shebang procedural script, wired
        exactly like the test suite (real repositories/services over a real `ConnectionFactory`
        connection), refuses to run under any SAPI but `cli` (a web-reachable-by-path script must
        never be triggerable by an anonymous HTTP request), paired with a `.htaccess`
        (`Require all denied`) in the same directory as defense in depth, not a substitute for that
        guard. Verified with `php -l` (syntax only — not run against a live database in this pass; no
        change to this environment's database availability since T1.3).
      - **Known limitation surfaced, not part of this task's own diff, reported rather than
        silently fixed**: a from-scratch `vendor/bin/phpcs` run found 105 pre-existing files (T1.5
        through T2.6, plus 2 files this task itself touched — `ImportJobRepository.php` and
        `ImportJobRunnerTest.php`) with CRLF line endings where the ruleset expects LF — root-caused
        to this machine's `git config core.autocrlf=true` with no `.gitattributes` to override it, not
        a defect in any of those files' actual content. Confirmed with you: left as-is, to be fixed in
        its own dedicated pass (`.gitattributes` + `phpcbf`), not bundled into this task's diff.
      - **Verified for real**: `vendor/bin/phpunit` — **236/236 tests, 643 assertions**, all passing
        (10 new: 3 `ConnectionFactoryTest`, 2 `TemplateResolverTest`, 5 `ImportJobRunnerTest` covering
        empty-queue, multi-job processing, FIFO ordering, the `$maxJobsPerRun` bound, and per-job
        failure isolation). `vendor/bin/phpstan analyse` — `[OK] No errors` at level 8.
        `vendor/bin/phpcs --standard=.phpcs.xml` — **0 errors, 0 warnings among files this task
        touched**; 105 pre-existing CRLF errors remain repo-wide, see the Known Limitation above (not
        claimed as clean — reported exactly as found). `php -l
        public_html/api/cron/import_runner.php` — no syntax errors. `composer dump-autoload` — clean
        (same two pre-existing PSR-4 casing notes as T2.3/T2.5's fixture classes).

## Done
- [x] Duplicate Detection + Audit Trail — T2.6 (FR-007/FR-008 —
      [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task)) — **Approved.** Given directly by instruction,
      scoped to Duplicate Detection + Audit Trail only: no cron runner (T2.7), no analytics, no
      dashboard, no AI.
      - **Analytics/Audit layer** (`app/Import/Audit/`, all new): `DuplicateDetectionService`
        (FR-007 — four independent checks per import job: within-file repeated `student_id`,
        already-imported per `(student_id, assessment_id)` via the existing but previously-unwired
        `StudentScoreRepository::existsForStudentAndAssessment()`, a colliding `import_jobs` row for
        the same `(school, assessment, file_path)` via the existing but previously-unwired
        `ImportJobRepository::findBySchoolAssessmentAndPath()`, and another `queued`/`processing`
        job for the same school+assessment via the new
        `findActiveJobsForSchoolAndAssessment()` — every check excludes the current job from
        flagging itself), `DuplicateCheckResult` (structured DTO — never null/void, so a duplicate
        can never be silently dropped; `summary()` builds a safe, human-readable message from
        counts/ids only, never raw exception text), `AuditEvent` (typed DTO — `event`/`status`/
        `schoolId`/`actorId`/`message`/`context`/`occurredAt`), `ImportAuditLogger` (writes only the
        four new event types — refuses, by a hard `InvalidArgumentException` guard, to write any of
        the six pre-existing events `ImportJobManager` already owns, so no occurrence is ever logged
        twice), `AuditTrailService` (the read/write façade: `recordImportStarted()`/
        `recordDuplicateFound()`/`recordRetry()`/`recordRollback()`, plus `timelineFor()` —
        reconstructs the full, typed timeline for one job from `import_logs` + a single
        `import_jobs.school_id` join, regardless of which class wrote each row).
      - **Vocabulary change, documented, not silent**: `import_logs.event` extended from 6 to 10
        values (`duplicate_found`, `import_started`, `retry`, `rollback`) —
        [decisions/IDR-008](decisions/IDR-008-import-audit-event-vocabulary-extension.md), with
        matching Post-Freeze Amendments to `docs/03-Database-Design.md` (→2.0.2),
        `docs/Data-Dictionary.md` (→1.0.2), and `docs/00-Project-Overview.md` §13 (→2.0.8, 7th
        amendment entry). Investigated first, per instruction, whether the existing 6-value
        vocabulary could just be reused with a distinguishing message (the precedent
        `RetryFailedImport` already set for "retry" in T2.4) — rejected because it would make the
        audit trail materially less queryable by event type, which is what FR-008's "queryable by
        actor" acceptance criterion depends on.
      - **Pipeline integration** (`app/Import/Score/ScoreImportService.php`,
        `app/Import/Session/RetryFailedImport.php`, both modified, both already-approved): duplicate
        check runs once, right after mapping and before template validation (fail fast — a duplicate
        file shouldn't need column-level validation to be rejected); `import_started` logged right
        after `markProcessing()`; `rollback` logged if the commit transaction throws, **in addition
        to**, not instead of, the existing `rejected` event `markFailed()` already logs for the same
        failure; `retry` logged by `RetryFailedImport` in addition to its existing `queued`+message
        convention (unchanged, so already-approved T2.4 behavior is preserved, not replaced).
      - **Repository layer**: one new finder,
        `ImportJobRepository::findActiveJobsForSchoolAndAssessment()` — CRUD-adjacent, no business
        logic, matches the established finder-method style; no repository calls another repository
        anywhere in this task's code (Architecture Rule).
      - **Security**: every message `AuditTrailService`'s convenience methods construct is built
        from structured data the caller already legitimately holds (counts, ids, student ids) —
        never from a raw `Throwable`/PDO message; verified directly by three tests asserting no
        `duplicate_found`/`rollback` message ever contains `SQLSTATE`, `PDOException`, or
        `Stack trace`. The existing `error_detail`/`import_logs.message` diagnostic channel
        (`Commit failed: {raw exception}`, unchanged since T2.3) is left as-is — an established,
        already-reviewed T2.3 pattern where the raw detail is filtered at the presentation layer
        (`RowErrorCollector`, T2.4), not at the point of storage; relitigating that boundary was out
        of this task's scope.
      - **Known limitations**: (1) "duplicate by question" is not applicable in v1.0 — no import
        pipeline writes `student_question_responses` yet (only aggregate `student_scores`), so there
        is nothing to check against; not built, per YAGNI, rather than added as unreachable code. (2)
        "Hash comparison if available": `DuplicateDetectionService::contentHash()` computes an
        order-independent SHA-256 of each file's (student_id, score) pairs and exposes it on
        `DuplicateCheckResult`, but no `file_hash` column exists anywhere in the schema to compare it
        against import history — introducing one is a schema change outside this task's scope, so
        the hash is available, not yet wired to persistent comparison. (3) `import_jobs`'s own
        `(school_id, assessment_id, file_path)` unique key structurally can rarely fire in practice —
        `UploadService::stagingPathFor()` (T2.1) generates a fresh random-token path per upload by
        design — so the effective FR-007 mechanisms are the already-imported and active-job checks,
        not the file-path collision check, which remains wired in only for defense-in-depth.
      - **Bug found and fixed while writing this task's own tests**: a new
        `RetryFailedImportTest` scenario initially failed — `assertTrue($result->success)` on the
        retry — traced to the test's own setup, not production code: `RetryFailedImportTest::setUp()`
        deliberately omits student `S002` (that absence is the fixture for the file's *other*,
        already-approved retry test), so retrying past a cleared duplicate condition still failed on
        an unrelated missing-student error until the new test explicitly re-added `S002` before
        retrying — fixed in the test, no production code was at fault.
      - **Verified for real**: `composer dump-autoload` — clean (same two pre-existing PSR-4 casing
        notes as T2.3/T2.5's fixture classes, unrelated to this task). `vendor/bin/phpunit` —
        **226/226 tests, 610 assertions**, all passing (44 new: 12
        `DuplicateDetectionServiceTest`, 11 `ImportAuditLoggerTest`, 15 `AuditTrailServiceTest`, 3
        new `ScoreImportServiceTest` duplicate-path cases, 1 new `RetryFailedImportTest`
        retry-after-duplicate case, 2 new `ImportJobRepositoryTest` cases for the new finder).
        `vendor/bin/phpstan analyse` — `[OK] No errors` at level 8. `vendor/bin/phpcs
        --standard=.phpcs.xml` — 0 errors, 0 warnings across 107 files. Quality gates passed,
        documentation reconciled, no outstanding architecture issues remain, review complete.
- [x] Import Engine Foundation — T2.1 (Upload Service, Import Job Manager, File Validation, Excel
      Reader, CSV Reader — [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task)) —
      **Approved.** **Scoped down per instruction**: no PDF reader (T2.1 also lists `PdfParser`; not requested this pass),
      no score import (Tasks T2.3–T2.6), no analytics, no AI. "Excel Reader"/"CSV Reader" map to the
      frozen `ExcelParser`/`CsvParser` class names `IMPLEMENTATION_GUIDE.md` §5 already specifies.
      - **Data layer** (`app/Repository/`): `ImportJobRepository`, `ImportLogRepository` — pure
        CRUD, matching `import_jobs`/`import_logs` exactly.
      - **Service layer** (`app/Import/`, business logic): `ImportJobManager` (owns the
        queued→processing→committed/failed state machine and the FR-008 audit trail),
        `FileValidationService` (FR-003's 50 MB limit and FR-004/FR-005's "MIME type, not merely
        filename extension" rule — verified against real generated `.xlsx`/`.csv` files),
        `UploadService` (validates, stages to `storage/imports/`, registers the queued job —
        deliberately does not touch `$_FILES`/`move_uploaded_file()`; deliberately does not
        implement FR-007 duplicate detection, needs the *parsed* student set, Task T2.5).
      - **Parser layer** (`app/Import/Parser/`): `ExcelParser` (PhpSpreadsheet,
        [decisions/IDR-001](decisions/IDR-001-phpspreadsheet-for-excel-import.md)), `CsvParser`
        (FR-005: delimiter-configurable, UTF-8/TIS-620 auto-detect via `iconv()`, not
        `mb_convert_encoding()` — verified this PHP build's mbstring does not register "TIS-620" as
        a valid encoding name, unlike `iconv()`).
      - Three real bugs found and fixed during implementation (fabricated PhpSpreadsheet exception
        class name; `fgetcsv()`'s `[0 => null]` blank-line shape; an empty `.xlsx` sheet's
        `toArray()` shape) — see `CHANGELOG`-equivalent detail in git history for this task.
      - `storage/` (`imports/`, `logs/`, `cache/`) created and `.gitignore`d (student PII risk).
      - **Verified for real** at the time of this entry: PHPUnit/PHPStan/PHPCS all clean — exact
        counts superseded by later entries below as more code was added on top; not re-quoted here
        to avoid drift. Quality gates passed, documentation reconciled, no outstanding architecture
        issues remain, review complete.
- [x] Import Session & Error Reporting — T2.4 (ImportSessionService, ImportSummary,
      RowErrorCollector, ImportErrorReport, RetryFailedImport, DownloadErrorCsv, ImportHistory —
      [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task)) — **Approved.** Given directly by instruction;
      `IMPLEMENTATION_GUIDE.md` v1.1.0 was subsequently amended (see its Revision History) so its own
      T2.4 now reads exactly this way — Normalization (FR-009) moved to T2.5 (see this sprint's
      Review section above; not yet approved). Purely a reporting/read layer on top of the
      already-approved Score Import Pipeline (T2.3) — no new pipeline logic, no analytics, no AI,
      no dashboards.
      - **Data layer** (`app/Repository/ImportJobRepository.php`, one addition): `findBySchool()` —
        pure CRUD, matching the existing repository's finder-method style.
      - **Session layer** (`app/Import/Session/`, all new): `ImportSessionService` (thin facade over
        the reused `ScoreImportService`/`ImportResult` — `run()` delegates unchanged, `summarize()`
        and `buildErrorReport()` reshape the same `ImportResult` for display), `ImportSummary`
        (presentation-ready outcome — job id, status, row counts, one human-readable message),
        `RowError` (traceable row-level unit: row number + message), `RowErrorCollector` (parses
        `ImportResult::$rowErrors`'s `"Row N: message"` strings, and the pipe-joined
        `import_jobs.error_detail` text `ImportJobManager::markFailed()` already persists, into
        `RowError[]` — this is also where "no internal exceptions exposed" is enforced: a
        commit-time failure's raw exception message (which can be a raw DB/driver error) is replaced
        with a generic safe message here, never shown verbatim, while the raw text still exists in
        `import_jobs.error_detail`/`import_logs` for diagnostics), `ImportErrorReport` (the full
        structured report for one failed job), `RetryFailedImport` (re-queues a `failed` job —
        rejects retrying a job in any other status — and reruns it through `ImportSessionService`,
        reusing `ImportJobManager`/`ImportJobRepository` unchanged), `DownloadErrorCsv` (renders an
        `ImportErrorReport` as `row,message` CSV text), `ImportHistory` (`forSchool()` — reuses the
        new repository finder; `timeline()` — reuses `ImportJobManager::history()` unchanged, FR-008;
        `errorReportFor()` — reconstructs an `ImportErrorReport` for a **past** failed job straight
        from the persisted `error_detail`, so a report is downloadable without re-running the import).
      - **Known limitation, noted rather than worked around**: `ImportResult::$rowErrors` already
        flattens multiple field-level messages into one semicolon-joined string per row before
        `RowErrorCollector` ever sees it, so `RowError` captures (row number, message) but not
        individual field names — reusing `ImportResult` as instructed means working within the
        granularity it already provides, not reverse-engineering field names out of message text.
      - **Verified for real**: `vendor/bin/phpunit` — **149/149 tests, 399 assertions**, all passing
        (18 new tests under `tests/Unit/Import/Session/`, including two end-to-end cases through the
        real Golden Dataset pipeline and a retry scenario that fails once on an unresolvable
        `student_id`, then succeeds after the underlying condition is fixed and the job is retried).
        `vendor/bin/phpstan analyse` — `[OK] No errors` at level 8. `vendor/bin/phpcs
        --standard=.phpcs.xml` — 0 violations across 74 files. Quality gates passed, documentation
        reconciled, no outstanding architecture issues remain, review complete.
- [x] Score Import Pipeline — T2.3 (ScoreImportService, StudentResolver, AssessmentResolver,
      ScoreNormalizer, RowValidator, ImportTransactionService, ImportResult —
      [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task)) — **Approved.** **Scoped per
      instruction**: no analytics (`standard_performance_summary` recompute), no AI, no dashboards.
      Reuses T2.1/T2.2
      throughout — `ParserInterface`, `MappingInterface`, `TemplateRegistry`/`TemplateValidator` —
      rather than re-implementing any of it.
      - **Data layer** (`app/Repository/`, two new): `AssessmentRepository` (pure CRUD),
        `StudentScoreRepository` (pure CRUD; `student_scores` rows are immutable per
        `docs/03-Database-Design.md` §13, so `update()`/`delete()` only satisfy
        `AbstractRepository`'s contract, normal flow never calls them).
      - **Score layer** (`app/Import/Score/`, new): `StudentResolver`/`AssessmentResolver`
        (referential-integrity checks, FR-006 — read-only, never invent a record for an unknown
        id), `ScoreNormalizer` (format + real float 0.00–100.00 range check — **deliberately does
        not use** `Dmf\Core\Validation\Rules\IntRangeRule`, verified directly that it casts to
        `(int)` before comparing, so `"100.5"` would incorrectly pass a 0–100 check), `RowValidator`
        (wraps the reused `ValidatorInterface`, adds one cross-row check it can't express alone —
        no two rows in the same file may share a `student_id`), `ImportTransactionService` (wraps
        the real `Dmf\Core\Contract\ConnectionInterface::transaction()` — commits every row of one
        job in a single transaction, rolls back entirely on any failure), `ImportResult` (outcome
        value object), `ScoreImportService` (the orchestrator: parse → map → validate → resolve →
        normalize → commit, logging `parsed`/`mapped`/`validated`/`committed`/`rejected` per
        FR-008 — **the caller resolves which `ImportTemplate` applies and passes it in**; this
        service does not guess a template key from assessment metadata, since no documented
        convention establishes one).
      - **One real bug fixed in already-approved T2.1 code, found while building this task:**
        `ImportJobManager::markProcessing()` was logging `import_logs.event = 'processing'` —
        `docs/Data-Dictionary.md` §5 is explicit the only valid values are `queued`, `parsed`,
        `validated`, `mapped`, `committed`, `rejected`. Fixed to update `import_jobs.status` only,
        no invalid log entry; its test updated to match.
      - **`ExcelParser` also fixed** (same real-bug-while-building category): a fully blank
        *interior* row is now skipped, matching how `CsvParser` already skips a blank CSV line —
        previously an interior blank row fell through to validation as a phantom all-empty row.
      - **Golden Test Dataset** (`tests/fixtures/import/`, real files, not typed by hand):
        `valid_onet.xlsx`/`.csv`, `missing_student_id.xlsx`, `duplicate_student.xlsx`,
        `invalid_score.xlsx`, `missing_required_column.xlsx`, `blank_rows.xlsx`, `utf8.csv`,
        `tis620.csv` — generated by `GoldenDatasetGenerator.php` (`generate.php` is the entry
        script; kept out of the class to satisfy PSR-1's no-mixed-declarations-and-side-effects
        rule, a real PHPCS finding). Every fixture uses a second, still test-only example template,
        `ExampleTemplates::studentIdAndScore()` — same "not a real ONET file layout" disclaimer as
        `studentIdOnly()`. Each fixture's expected outcome was verified against the real pipeline
        before being written into `ScoreImportServiceTest`, not assumed — see the README in that
        directory for the full table.
      - **Verified for real**: `vendor/bin/phpunit` — **131/131 tests, 335 assertions**, all
        passing, including `ScoreImportServiceTest`'s 11 end-to-end tests running the real Golden
        Dataset through the real `ExcelParser`/`CsvParser` → `ColumnMapper` →
        `RowValidator`/`TemplateValidator` → resolvers → `ScoreNormalizer` →
        `ImportTransactionService` pipeline over a stateful mocked `ConnectionInterface`.
        `vendor/bin/phpstan analyse` — `[OK] No errors` at level 8 (one real finding fixed along
        the way: a possibly-null score reaching the commit array, caught by PHPStan, fixed by
        making the score-presence check unconditional rather than dependent on template
        configuration). `vendor/bin/phpcs --standard=.phpcs.xml` — 0 violations across 60 files.
- [x] Template Registry & Mapping Framework — T2.2 (Contracts, Column Mapping Framework, Template
      Registry — [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task)) — **Approved.**
      - **Contracts layer** (`app/Import/Contracts/`): `ParserInterface` (relocated here from
        `app/Import/Parser/`), `MappingInterface` (new), `ValidatorInterface` (new).
        `ExcelParser`/`CsvParser` implement `ParserInterface`; `ColumnMapper` implements
        `MappingInterface`; `TemplateValidator` implements `ValidatorInterface` — a future
        `JsonParser`/`XmlParser` (not built) has a contract to implement, not a new shape to invent.
      - **Column Mapping Framework** (`app/Import/Template/`): `ColumnMapping` (canonical field ↔
        header-alias dictionary, e.g. FR-004's "รหัสนักเรียน"/"เลขประจำตัว" example),
        `ColumnMapper` (reshapes positional parsed rows into rows keyed by canonical field name),
        `MappingResult`.
      - **Template Registry**: `ImportTemplate` (key, mapping version, `ColumnMapping`,
        required/optional columns, declarative `validationRules` using
        `Dmf\Core\Validation\Validator`'s own rule syntax), `TemplateRegistry` (in-memory lookup by
        key), `TemplateValidator` (checks required columns present/non-empty, then runs
        `validationRules` through the real `Dmf\Core\Validation\Validator`). **Only one template is
        registered**: `ExampleTemplates::studentIdOnly()` (key `EXAMPLE-STUDENT-ID-ONLY`), using
        only the one alias pair FR-004 actually documents. The five real named templates
        (`ONET-2569`, `ONET-2570`, `NT`, `RT`, `School Assessment`) were **not** built with invented
        column lists — no frozen doc specifies a real ONET file's exact required/optional columns
        beyond that one example; fabricating one would be the same category of risk already
        declined for the standards catalogue during T1.4 (Seeder). Confirmed with you before
        proceeding — framework is real and tested, concrete production templates still need the
        real สทศ file specification.
      - **Verified for real**: `vendor/bin/phpunit` — 93/93 tests, 245 assertions, all passing
        (`ExampleTemplatesTest` exercises the full Parser → Mapping → Validation pipeline end to
        end). `vendor/bin/phpstan analyse` — `[OK] No errors` at level 8. `vendor/bin/phpcs` — 0
        violations across 41 files.

---

# Sprint 3 – Web Application Foundation

Maps to [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) Phase 1, Tasks T1.6–T1.7 (Auth,
role-scoped dashboard shell — deliberately deferred out of Sprint 1, see that sprint's Done entry).
**Architecture note, resolved before any code was written**: the kickoff instruction described a
session/CSRF/multi-page server-rendered app; that conflicts with the frozen
[docs/02-System-Architecture.md](docs/02-System-Architecture.md) (SPA + JSON API + Bearer token, no
PHP session, matching `grade.dmf.ac.th`) and with `dmf-core`'s `Http\Response` (JSON-only, cannot
render HTML). Confirmed with you before building — the documented SPA architecture was chosen, no
ADR needed. Full reasoning, the requested-class-name → `dmf-core`-class mapping, and every concrete
design choice this forced: [decisions/IDR-010](decisions/IDR-010-web-application-foundation.md).

## Todo

## In Progress

## Review

## Done
- [x] Web Application Foundation — T1.6/T1.7 (Staff Authentication (FR-001) + Role-Scoped Dashboard
      Shell (FR-002) — [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task)) — **Approved**
      (committed `a053f65` "feat(web): complete Sprint 3 - Web Application Foundation", tagged
      `v0.4.0-web-foundation`, merged to `main`). Given directly by instruction, re-scoped to the
      SPA architecture per [decisions/IDR-010](decisions/IDR-010-web-application-foundation.md).
      No analytics yet — dashboard shell only.
      - **Auth layer** (`app/Auth/`, new — mirrors `grade.dmf.ac.th`'s actual `app/Auth/*` layout,
        not the illustrative `app/Action/*` tree in `02-System-Architecture.md §5`, which no module
        built so far has actually followed): `StaffTokenManager extends Dmf\Core\Auth\TokenManager`
        (`authenticate()` against `StaffUserRepository` + the real `Security\PasswordHasher`; runs
        the bcrypt comparison unconditionally, even for an unknown username, against a fixed dummy
        hash, so a nonexistent-username request and a wrong-password request take the same server
        time — a real hardening beyond what any doc explicitly asked for), `StaffRateLimiter extends
        RateLimiter` (MySQL-backed via the new `LoginRateLimitRepository`, not `$_SESSION`-backed
        like `grade.dmf.ac.th`'s equivalent — this module has no PHP session at all), `StaffGuard
        extends Guard` (`throttleKey()` only — `login()`/`user()`/`logout()` are already correct in
        the abstract base).
      - **Class-name mapping**: the instruction named `AuthenticationService`/`SessionManager`/
        `AuthMiddleware`. `AuthenticationService` → `StaffGuard`+`StaffTokenManager` (`dmf-core`'s
        `Guard`/`TokenManager` pair *is* the authentication service). `SessionManager` → **not
        built** — there is no server-side session to manage in a Bearer-token SPA; building one
        would be inventing an unused abstraction. `AuthMiddleware` → `DMF\Http\Middleware\StaffAuthMiddleware
        extends Dmf\Core\Http\Middleware\AuthMiddleware` (maps directly).
      - **Data layer** (`app/Repository/`, three new, all pure CRUD): `StaffUserRepository`
        (`findByUsername()`; `delete()` soft-deletes via `deleted_at`, matching
        `docs/03-Database-Design.md §3`'s documented "soft delete on account deactivation" — never
        a hard `DELETE`), `LoginRateLimitRepository` (`findByUsername()`), `SchoolRepository`
        (minimal — only the inherited `findById()` is needed, to resolve a login's `school_id`
        claim to a display name).
      - **Action layer** (`app/Action/Auth/`, `app/Action/Dashboard/`, new — *this* tree does match
        `02-System-Architecture.md §5`, since one-class-per-route HTTP handlers are exactly what it
        names; the two namespace conventions aren't actually in conflict, see IDR-010 §7):
        `LoginStaffAction` (thin — all business logic in `Guard::login()`; an empty/wrong
        username-password, a locked account, an inactive account, and a soft-deleted account all
        surface as the same `AuthException`, which `Router::dispatch()` already converts to the
        correctly-coded JSON error), `LogoutStaffAction` (best-effort — `TokenManager::revoke()` is
        a documented no-op for this stateless token; the real logout is the client discarding its
        token), `DashboardSummaryAction` (App Version, Logged-in User, School Name, Import
        Statistics, Recent Import Jobs, System Status — every field genuinely computed, no
        fabricated metrics; "System Status" is deliberately minimal — PHP version, timezone, a
        database-reachable flag — since no monitoring infrastructure exists yet).
      - **No `Gate`/`Policy` layer built** — `StaffAuthMiddleware`'s constructor already accepts an
        optional `$requiredRole` ("future-ready for additional roles" without a new class per role);
        building a parallel authorization layer for a check this simple would be speculative
        (YAGNI) — see IDR-010 §6 for the full reasoning.
      - **Front controller + SPA shell** (`public_html/`, both new — the SPA shell is the first
        `public_html/index.html` this project has ever created; `api/index.php` is the second
        `public_html/*.php` file, after T2.7's cron entry point): `public_html/api/index.php` wires
        every class above and dispatches via `dmf-core`'s `Http\Router` on `login_staff`/
        `logout_staff`/`dashboard_summary`. `public_html/index.html` (Bootstrap 5 via CDN, vanilla
        JS, no build step) + `public_html/assets/js/app.js`: login form → token in
        `sessionStorage` (never a cookie — no ambient credential, hence no CSRF, per IDR-010 §1) →
        dashboard fetch/render → logout clears the token. Every server-provided string is inserted
        via `textContent` or an explicit `escapeHtml()` helper, never raw `innerHTML` interpolation.
        Responsive via Bootstrap's grid + an offcanvas sidebar on mobile.
      - **Security**: CSRF/session-fixation/"password never in session" are all inapplicable by
        architecture (no PHP session exists to fixate or leak a password into) — documented in
        IDR-010 rather than built as no-op code. Two real, independently-found issues were fixed
        while building this task: (1) `config/app.php` had a global `readVersionFile()` function
        declared inside a file this front controller now `require`s per request — a latent "cannot
        redeclare function" risk, self-identified during Module 1 (Sprint 1) and never fixed until
        now; replaced with an inline closure. (2) `dmf-core`'s own `Router::dispatch()` puts a raw
        `Throwable::getMessage()` (confirmed directly: a DB connection failure leaked
        `"SQLSTATE[HY000] [2002] ..."` verbatim) straight into a 500 JSON response — not something
        this project can change inside `dmf-core`, so `public_html/api/index.php` now replaces a
        500 response's message with a generic one whenever `APP_DEBUG` is off, found and fixed via
        a real dry-run of the front controller (`php -r ...`, not just unit tests) before calling
        this task done. Security headers (`X-Frame-Options`, `X-Content-Type-Options`) set both
        server-side (`index.php`) and via `public_html/.htaccess` (covers the static SPA files
        those PHP headers never touch).
      - **Known limitation**: no live end-to-end run against a real database — this environment has
        no MySQL/MariaDB currently running (same limitation noted since T1.3). The front controller
        was dry-run via CLI (`php -r ...`) far enough to confirm correct wiring and correct JSON
        error shapes (401/403/404/500) without a database, and the DB-dependent path
        (`login_staff`) was confirmed to fail gracefully (a clean JSON 500, not a raw PHP fatal
        error) when no database is reachable — real credential verification against a live
        `staff_users` table is covered by `StaffTokenManager`/`StaffGuard`'s unit tests (real
        `PasswordHasher`, real repositories, mocked `ConnectionInterface`), not by a live run.
      - **Verified for real**: `composer dump-autoload` — clean (same two pre-existing PSR-4 casing
        notes as T2.3/T2.5's fixture classes). `vendor/bin/phpunit` — **280/280 tests, 751
        assertions**, all passing (44 new: 5 `StaffUserRepositoryTest`, 5
        `LoginRateLimitRepositoryTest`, 3 `SchoolRepositoryTest`, 7 `StaffTokenManagerTest`, 6
        `StaffRateLimiterTest`, 6 `StaffGuardTest`, 5 `StaffAuthMiddlewareTest`, 3
        `LoginStaffActionTest`, 2 `LogoutStaffActionTest`, 2 `DashboardSummaryActionTest`).
        `vendor/bin/phpstan analyse` — `[OK] No errors` at level 8. `vendor/bin/phpcs
        --standard=.phpcs.xml` — **0 errors, 0 warnings among files this task touched**; the
        113 pre-existing CRLF errors (T1.5 onward, `core.autocrlf` root cause — see T2.7's Done
        entry) remain, unrelated to this task and still deferred to its own pass per your standing
        instruction. `php -l` clean on both new `public_html/*.php` files. Manual dry-run of
        `public_html/api/index.php` via CLI confirmed correct 401 (`dashboard_summary` with no
        token), 403 (role-mismatch, via unit test), 404 (unknown action), and 500-sanitized
        (DB unreachable) responses.

---

# Sprint 4 – Analytics Engine

Maps to [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) Phase 3 (Standards Mapping & Analytics),
governed by [docs/rfcs/RFC-004](docs/rfcs/RFC-004-multi-source-analytics-architecture.md) (approved)
— Source Independence, the Level 1/2/3 Assessment Data Classification, Assessment Adapter Layer, and
Canonical Analytics Model (see `IMPLEMENTATION_GUIDE.md` Phase 3's intro and T3.2). **Phase 1**
(Analytics Domain Foundation — Repository → Service → DTO, no presentation layer) is complete and
approved; **Phase 2** (Analytics Calculators — the first generation of concrete calculators over
that foundation) is below, awaiting review. No dashboard, controller, API, chart, AI, Import Engine
change, or database change is in scope until a later phase of this same sprint.

## Todo

## In Progress

## Review
- [ ] Analytics Aggregation & Dashboard Data API — Sprint 4 Phase 3
      ([IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task), Phase 3 intro/T3.2,
      decisions/IDR-011) — given directly by instruction, scoped to Analytics Aggregation,
      Dashboard DTOs, Dashboard Data API, Dashboard Cache, Dashboard Health: no HTML, no charts, no
      JavaScript visualization, no AI recommendation. Import Engine and database schema untouched
      (one new read-only repository added, no migration).
      - **Real architecture conflict found and resolved before writing any Action, per
        [decisions/IDR-011](decisions/IDR-011-dashboard-api-architecture.md)**: the instruction
        specified REST-style paths (`GET /api/dashboard/overview`, etc.) and an implicit
        `assessment_id` query parameter; `Dmf\Core\Http\Router` only supports `"METHOD:action"`
        dispatch (its own docblock says so) and `Dmf\Core\Http\Request` (`final`) has no method to
        read an arbitrary `GET` query parameter at all — the same class of constraint
        [IDR-010](decisions/IDR-010-web-application-foundation.md) already resolved once for
        Sprint 3. Resolved the same way: `snake_case` `?action=` names preserving the resource
        intent (`dashboard_overview`, `dashboard_assessment`, `dashboard_subjects`,
        `dashboard_benchmark`, `dashboard_health` — matching `docs/Naming-Convention.md §3`, which
        already anticipated exactly this), and every Dashboard endpoint reports on **the latest
        registered assessment** (`AssessmentRepository::findLatest()`, new) rather than an
        unsupportable caller-supplied id. No new router, path parser, or `Request` subclass was
        built — see IDR-011 for the full reasoning and every alternative considered.
      - **Aggregation** (`app/Analytics/Aggregation/`, all new): `AssessmentSummaryAggregator`,
        `SubjectSummaryAggregator`, `StrandSummaryAggregator`, `StandardSummaryAggregator`,
        `BenchmarkAggregator` — each a pure reshape of one calculator's `Result\*` records (or, for
        Assessment, of `AnalyticsContext.assessmentRecord` directly, since no calculator produces an
        assessment-grain result) into a Dashboard DTO; no new computation anywhere.
        `AnalyticsAggregationService` exposes both `aggregate(AnalyticsContext, AnalyticsResultInterface[]): DashboardResponse`
        (the pure, no-I/O merge Module 1 describes — tested directly with hand-built fixtures) and
        `forLatestAssessment(): ?DashboardResponse` (the one method every Dashboard Action actually
        calls — internally: `AssessmentRepository` → `AnalyticsReadRepository` →
        `ItemIndicatorNormalizer` (T2.5) → `AnalyticsContextFactory` (Phase 1) → `AnalyticsPipeline`
        (Phase 1/2, unchanged) → `aggregate()`, optionally cached; see IDR-011 §3 for why this is
        one class, not a fifth unrequested layer). `DashboardHealthAggregator` builds Module 7's
        read-only snapshot from `ImportJobRepository`/`AssessmentRepository`/`StudentRepository`.
      - **Dashboard DTOs** (`app/Analytics/Dashboard/`, all new, all `final`/readonly):
        `DashboardMetadata`, `DashboardAssessment`, `DashboardSubject`, `DashboardStrand`,
        `DashboardStandard`, `DashboardBenchmark`, `DashboardCard`, `DashboardAlert` (+
        `DashboardAlertLevel` enum), `DashboardDataset`, `DashboardSummary`, `DashboardResponse`,
        `DashboardHealth` — plus `DashboardResponseSerializer`, the one class that turns every DTO
        into a plain, `snake_case` JSON-ready array, so all five Actions serialize identically
        instead of duplicating array-shaping five times. No HTML, no Bootstrap, no Chart.js
        anywhere in this namespace.
      - **Cache** (`app/Analytics/Cache/`, all new, Module 6): `DashboardCacheInterface extends
        Dmf\Core\Contract\CacheInterface` (a zero-new-method marker interface — `dmf-core` already
        provides the exact `get`/`set`/`delete`/`has`/`clear` cache-key/TTL/invalidate shape asked
        for; see IDR-011 §4 for why a parallel contract wasn't reinvented from scratch),
        `InMemoryDashboardCache` (the only implementation — plain in-process array, no Redis, per
        `docs/02-System-Architecture.md §16`'s shared-hosting constraint). Every consumer takes the
        cache as an optional (`?DashboardCacheInterface = null`) dependency, so the Dashboard Data
        API still computes a correct result when no cache is constructed at all.
      - **Repository** (`app/Repository/`): `AnalyticsReadRepository` (new) —
        `findResponsesForAssessment(int): array`, joining `student_question_responses` to
        `questions` by `assessment_id` (a column the responses table itself doesn't carry); read-only
        by a thrown `\LogicException` on `create()`/`update()`/`delete()`, not just by convention
        (IDR-011 §6). `AssessmentRepository::findLatest()` (new, additive) — `ORDER BY academic_year
        DESC, id DESC LIMIT 1`. `BenchmarkRepository`/`DashboardRepository` (Module 5's other named
        examples) were **not** built — no table anywhere stores a benchmark comparison figure yet
        (IDR-011 §5); building one against a non-existent table would repeat the exact
        fabricate-data mistake this project has consistently declined to make (T1.4, T2.2).
      - **Actions & Routes** (`app/Action/Dashboard/`, all new; `public_html/api/index.php`,
        modified): `DashboardOverviewAction` (the full `DashboardResponse`), `DashboardAssessmentAction`
        (metadata + assessments), `DashboardSubjectAction` (metadata + subjects + the strands/standards
        that only make sense nested under a subject), `DashboardBenchmarkAction` (metadata +
        benchmarks — empty today, honestly, see below), `DashboardHealthAction` (Module 7's
        snapshot). Every Action is thin — it calls `AnalyticsAggregationService`/
        `DashboardHealthAggregator` and serializes the result, never calculating anything itself
        (Architecture Rules). All five registered behind the existing `StaffAuthMiddleware` — any
        authenticated principal, matching `dashboard_summary`'s existing access level; no new
        role/policy was invented since nothing in this phase's scope asked for one.
      - **Honest current behavior, not a stub — see IDR-011 §7**: no Level 2 Assessment Adapter
        exists yet (RFC-004), so `student_question_responses` has no writer anywhere in this
        codebase and is empty in any real deployment. The Dashboard Data API is wired end-to-end
        against real repositories; it reports empty/all-zero figures today because that is the
        honest state of the data — not because anything is stubbed or faked. It starts reporting
        real figures the moment any future Level 2 Assessment Adapter commits rows, with no
        Dashboard-side code change required.
      - **Verified for real**: `composer dump-autoload` — 2423 classes (same two pre-existing PSR-4
        casing notes, unrelated). `vendor/bin/phpunit` — **397/397 tests, 1265 assertions**, all
        passing (64 new, including a full end-to-end orchestration test —
        `AnalyticsAggregationServiceTest::testForLatestAssessmentOrchestratesTheFullPipelineAgainstTheGoldenDataset` —
        driving the real Normalization Golden Dataset through every real repository, calculator, and
        aggregator over a mocked `ConnectionInterface`, plus a cache-hit test proving a second call
        is served from cache without re-querying). `vendor/bin/phpstan analyse` — `[OK] No errors`
        at level 8. `vendor/bin/phpcs --standard=.phpcs.xml` — **0 errors, 0 warnings among the 58
        files this phase touched** (several real line-length/multi-line-declaration findings in
        this phase's own new test files, found and fixed — one via `phpcbf` — before re-verifying).
        `php -l public_html/api/index.php` — no syntax errors. The pre-existing repo-wide CRLF
        findings (T2.7's Done entry) remain on files this phase's `Edit` calls touched a
        pre-existing line in (`AssessmentRepository.php`, its test, `DashboardSummaryAction.php`,
        its test) — same already-deferred issue, no new file affected by it.
      - **Known limitations**: `DashboardBenchmarkAction`/`BenchmarkCalculator` will report empty
        results in any real environment until a Level 1 Assessment Adapter populates
        `AnalyticsContext.benchmarkRecords` (unchanged limitation from Phase 2). `DashboardHealth.latestCalculation`
        is always `null` — the Analytics Engine persists nothing of its own, so there is no stored
        calculation history to report; every request recomputes live. Dashboard endpoints cannot
        target a specific historical assessment (only "latest") until `dmf-core`'s `Request` gains
        query-parameter support — out of this project's control to add unilaterally (IDR-011 §2).

## Done
- [x] Analytics Calculators — Sprint 4 Phase 2
      ([IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task), Phase 3 intro/T3.2) — given
      directly by instruction, scoped to the first generation of calculators over Phase 1's Domain
      Foundation: no discrimination, distractor analysis, student/indicator mastery, dashboard, REST
      API, charts, caching, AI recommendation, or export. Import Engine and database schema untouched.
      - **Contracts** (`app/Analytics/Contracts/`, new): `CalculatorPriority` (int-backed enum,
        `HIGHEST`(100)…`LOWEST`(0) — higher runs first), `CalculatorCapabilities` (a calculator's own
        declared Level 1/Level 2 support — never an inspection of what actually produced its input),
        `CalculatorExecutionContext` (wraps `AnalyticsContext` with one `executedAt` shared by every
        calculator in a single pipeline run). **Extended** `AnalyticsCalculatorInterface` (Phase
        1) — added `priority()`/`capabilities()`, and `calculate()` now takes a
        `CalculatorExecutionContext` instead of a raw `AnalyticsContext` — safe because Phase 1 built
        zero concrete calculators. **Extended** `AnalyticsResultInterface`/`AnalyticsResult` (Phase
        1) with a `records(): mixed[]` accessor — Phase 1 had no way to carry a calculator's actual
        computed output, only its summary counts; this is additive, every Phase 1 accessor is
        unchanged, and the two Phase 1 tests that exercise `build()` were updated to pass/assert
        `records` alongside what they already asserted.
      - **Canonical extension** (`app/Analytics/Canonical/`, new): `BenchmarkScope` (enum:
        school/province/region/country — RFC-004's evidenced O-NET comparison tiers),
        `BenchmarkAnalyticsRecord` (one externally-published comparison figure; populated only by a
        future Level 1 Assessment Adapter, none built yet). **Extended** `AnalyticsContext` (Phase 1)
        with `benchmarkRecords`, defaulted to `[]` so every Phase 1 call site (including
        `AnalyticsContextFactory`, which has no source of benchmark data) is unaffected.
      - **Pipeline** (`app/Analytics/Pipeline/AnalyticsPipeline.php`, modified): now sorts registered
        calculators by `CalculatorPriority` (descending, stable for equal priority) before running,
        rather than registration order, and wraps the `AnalyticsContext` in one shared
        `CalculatorExecutionContext` per run — exactly what "Pipeline must support future plug-in
        calculators" and "Execution order must use CalculatorPriority" asked for.
      - **Calculators** (`app/Analytics/Calculators/`, all new, each `priority()`/`capabilities()`
        declared, each reads only Canonical DTOs, never assessment type/source/provider):
        `DifficultyCalculator` (HIGH priority; per-question CTT p-value from
        `QuestionAnalyticsRecord.correctCount/responseCount` — the same pooled shape regardless of
        whether a Level 1 or Level 2 path populated it, so "supports Level 1 and Level 2" holds
        without the calculator ever branching on source); `BenchmarkCalculator` (LOW priority;
        compares each `benchmarkRecords` entry against the matching `SubjectAnalyticsRecord`'s own
        percent-correct — reads only what's already in the context, never fetches a comparison value
        itself); `StandardPerformanceCalculator` and `SubjectPerformanceCalculator` (NORMAL priority;
        percent-correct is always computed when responses exist; mean/median/min/max/standard
        deviation — and, for Subject, average/highest/lowest/distribution — are flagged one
        deliberate limitation, not silently skipped or fabricated: see below);
        `StrandPerformanceCalculator` (NORMAL priority; a flat, fully-computable `StrandSummary` —
        percent-correct plus the pooled counts, "no visualization" per instruction). Every calculator
        emits an `AnalyticsWarning`, never an exception, for any input it cannot compute from (zero
        responses, no matching benchmark subject).
      - **Result Model** (`app/Analytics/Result/`, all new): `DifficultyResult`, `BenchmarkResult`,
        `StandardResult`, `SubjectResult`, `StrandResult` — pure data, no dashboard formatting,
        embedded as each calculator's `AnalyticsResult::records()` payload.
      - **Design decision, flagged rather than silently resolved**: Mean/Median/Min/Max/Standard
        Deviation (Standard grain) and Average/Highest/Lowest/Distribution (Subject grain) all
        require a per-student score distribution that the current Canonical Analytics Model does not
        carry — `StandardAnalyticsRecord`/`SubjectAnalyticsRecord` only hold pooled
        `studentCount`/`responseCount`/`correctCount`, per Phase 1's own "raw counts only, no
        fabrication" discipline. Rather than inventing a per-student field Phase 1 didn't build, or
        silently dropping the requirement, both calculators compute what genuinely is available
        (percent-correct) and return `null` for the rest, each accompanied by an `AnalyticsWarning`
        naming exactly which statistics are unavailable and why — "if unavailable return
        AnalyticsWarning instead of throwing exceptions," applied uniformly rather than only where
        the instruction happened to say it explicitly (Module 4). Extending the Canonical Model with
        a real per-student distribution, if wanted, is a follow-on decision, not assumed here.
      - **Verified for real**: `composer dump-autoload` — 2365 classes (same two pre-existing PSR-4
        casing notes, unrelated). `vendor/bin/phpunit` — **333/333 tests, 1029 assertions**, all
        passing (35 new: 6 new Canonical/Contracts DTO tests — `BenchmarkAnalyticsRecord`,
        `BenchmarkScope`, `CalculatorPriority`, `CalculatorCapabilities`, `CalculatorExecutionContext`,
        plus `AnalyticsContext`'s new default-value case — 5 new Result DTO tests, 20 calculator tests
        across all five calculators including every "unavailable data → warning, not exception" path,
        4 rewritten `AnalyticsPipelineTest` cases proving priority-order execution beats registration
        order and equal-priority calculators keep registration order). `vendor/bin/phpstan analyse` —
        `[OK] No errors` at level 8. `vendor/bin/phpcs --standard=.phpcs.xml` — **0 errors, 0
        warnings among the 59 files this phase touched** (one real line-length warning found in this
        phase's own `AnalyticsPipelineTest.php` — an overlong test method name — fixed and
        re-verified before reporting). No new pre-existing-CRLF files were touched.
      - **Known limitations**: `AnalyticsContext.benchmarkRecords` is populated only by hand-built
        test fixtures today — no Assessment Adapter exists yet to supply a real benchmark figure, so
        `BenchmarkCalculator` produces zero records in any real run until one is built (expected,
        not a defect). `AnalyticsAggregatorInterface`/`AnalyticsDataProviderInterface` remain
        unimplemented, unchanged from Phase 1.
- [x] Analytics Domain Foundation — Sprint 4 Phase 1
      ([IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task), Phase 3 intro/T3.2) — given
      directly by instruction, scoped to the Analytics Domain Layer only: no calculators, no
      difficulty/discrimination/distractor/benchmark, no aggregation logic, no dashboard, no
      controller, no API, no chart, no AI, no SQL/migration change. The Import Engine and database
      schema were not touched.
      - **Contracts** (`app/Analytics/Contracts/`, all new, no implementations):
        `AnalyticsCalculatorInterface`, `AnalyticsResultInterface`, `AnalyticsAggregatorInterface`,
        `AnalyticsDataProviderInterface`. Each interface's docblock states the source-independence
        rule directly: an implementation must never inspect assessment type, source name, provider,
        or report format — only the Assessment Adapter Layer (docs/02-System-Architecture.md §8.1)
        knows those.
      - **Canonical Analytics DTOs** (`app/Analytics/Canonical/`, all new, all `final`/readonly):
        `AnalyticsMetadata` (assessment/subject/year/grade identity, no source field),
        `AssessmentAnalyticsRecord`, `SubjectAnalyticsRecord`, `StrandAnalyticsRecord`,
        `StandardAnalyticsRecord`, `QuestionAnalyticsRecord` (raw `studentCount`/`responseCount`/
        `correctCount` tallies only — deliberately **no** `percentCorrect`, `difficultyIndex`, or any
        other derived statistic; computing one is a future Calculator's job, not built in this
        phase), `AnalyticsContext` (the one object every calculator will receive). `QuestionAnalyticsRecord.standardId`
        names the question's *primary* standard only — indicator grain is deliberately not modeled,
        consistent with RFC-004/docs/03-Database-Design.md §9's still-open indicator-vs-standard
        grain question.
      - **Context layer** (`app/Analytics/Context/AnalyticsContextFactory.php`, new):
        `fromNormalizationResult(NormalizationResult, AnalyticsMetadata): AnalyticsContext` — pure
        grouping and tallying of Normalization's (T2.5) existing output up through
        question → standard → strand → subject → assessment grain. No Assessment Source logic: the
        factory only ever reads each `NormalizedRecord`'s already-resolved standard/strand chain,
        never an assessment type, source name, or provider. No percentage/index/average is computed
        here — only counts, so this stays a Domain Foundation piece, not a calculator.
      - **Pipeline** (`app/Analytics/Pipeline/AnalyticsPipeline.php`, new):
        `run(AnalyticsContext): AnalyticsResultInterface[]` — executes every registered calculator
        once, in registration order, against the same context. Pipeline only; zero calculators are
        registered by anything in this phase, since `AnalyticsCalculatorInterface` has no
        implementation yet.
      - **Result layer** (`app/Analytics/Result/`, all new): `AnalyticsResult` (implements
        `AnalyticsResultInterface`; `build()` derives an `AnalyticsSummary`'s counts from the
        supplied warnings/issues), `AnalyticsWarning`, `AnalyticsIssue` (both `identifier` + `message`
        — non-fatal vs. a specific record-level problem), `AnalyticsSummary` (calculator name, record
        count, issue/warning counts, computed-at timestamp — no dashboard formatting).
      - **Verified for real**: `composer dump-autoload` — 2335 classes (same two pre-existing PSR-4
        casing notes as T2.3/T2.5's fixture classes, unrelated to this phase). `vendor/bin/phpunit`
        — **298/298 tests, 874 assertions**, all passing (18 new: 7 Canonical DTO tests, 5 Result
        tests, 3 Pipeline tests covering empty/sequential/ordered execution via mocked calculators,
        3 `AnalyticsContextFactoryTest` cases including a full multi-grain grouping/tallying
        assertion across a hand-built two-strand/three-standard/four-question fixture).
        `vendor/bin/phpstan analyse` — `[OK] No errors` at level 8. `vendor/bin/phpcs
        --standard=.phpcs.xml` — **0 errors, 0 warnings among the 30 files this phase touched** (one
        real PSR-12 line-length warning found and fixed in `AnalyticsContextFactoryTest.php` during
        this phase's own verification); the pre-existing repo-wide CRLF line-ending findings (T2.7's
        Done entry — `core.autocrlf` root cause, still deferred to its own pass) remain, unrelated to
        this phase.
      - **Known limitations, by design per this phase's explicit scope**: zero
        `AnalyticsCalculatorInterface`/`AnalyticsAggregatorInterface`/`AnalyticsDataProviderInterface`
        implementations exist — `AnalyticsPipeline` is exercised only via mocked calculators in
        tests. `AnalyticsContextFactory` is not wired behind `AnalyticsDataProviderInterface`, since
        nothing yet calls it that would need that abstraction (YAGNI). No repository or database read
        was added — the factory's only input is `NormalizationResult`, already in memory from a
        Normalization run.

---

# Release Milestone — v0.5.0 "Analytics Engine Complete"

Not a feature sprint — a release-hardening pass over Sprint 4 (Phases 1–3) and RFC-004's now-approved
architecture: verify, stabilize, document, and prepare for production and the upcoming Dashboard
UI sprint. No new business feature, analytics algorithm, Dashboard UI, AI, or database change.

## Todo

## In Progress

## Review
- [ ] Release Hardening — v0.5.0 "Analytics Engine Complete"
      ([IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task), covering all of Sprint 4) — given
      directly by instruction: Architecture Verification, Codebase Audit, Pipeline Audit, API
      Audit, Performance Review, Security Review (static only), Documentation Audit, Versioning,
      Git Readiness, and a full quality-gate run. No refactor except to correct a genuine
      violation found during the audit (one: see below).
      - **Architecture Verification**: traced Repository → (Assessment Adapter, not yet built,
        RFC-004 Non-Goals) → Normalization (T2.5) → Canonical Analytics Model (Phase 1) →
        `AnalyticsPipeline` → 5 Calculators (Phase 2) → `AnalyticsAggregationService` (Phase 3) →
        Dashboard Data API. Confirmed by dependency-graph inspection (`use` statements across every
        `app/Analytics/*` sub-namespace): no source-specific vocabulary (assessment type, provider,
        report format) appears in any executable line, only in docblocks stating the rule; no
        `Action` imports a `Repository` except the pre-Analytics `DashboardSummaryAction`
        (Sprint 3, predates this rule, out of this milestone's scope); every calculator is
        dependency-free (no constructor at all) and imports no sibling calculator.
      - **One real, minor architectural wrinkle found, not a violation**: `Contracts` and `Result`
        namespaces mutually depend on each other (`AnalyticsResultInterface` references
        `AnalyticsWarning`/`AnalyticsIssue`/`AnalyticsSummary` from `Result`; `AnalyticsResult`
        implements `AnalyticsResultInterface` from `Contracts`) — a namespace-level cycle, not a
        class-level one (no two classes require each other to load), so autoloading, PHPStan, and
        every test are unaffected. Worth naming as technical debt (`Contracts`, ideally the most
        abstract layer, depends downward on `Result`'s concrete DTOs); not corrected here per
        "no refactoring unless required to correct violations" — nothing is actually broken.
      - **One real, previously-unreported PHPCS violation found and fixed**:
        `tests/Unit/Import/Cron/ImportJobRunnerTest.php` (T2.7-era, not touched by Sprint 4) had a
        genuine `PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine` error — a
        multi-line `if` condition's first expression shared the opening-parenthesis line. Extracted
        to a named boolean (`$isQueuedJobsLookup`), matching the pattern already used elsewhere in
        this codebase; re-verified PHPCS clean and the file's 5 tests still passing.
      - **One real, honest limitation found — not fixed, since fixing it is new work beyond this
        milestone's scope**: `InMemoryDashboardCache` is constructed fresh on every HTTP request
        inside the plain-procedural `public_html/api/index.php` front controller. Under standard
        PHP-FPM request-per-process execution, this cache **never survives between requests** — its
        300-second TTL is correct and its own unit tests genuinely prove the class's logic works
        within one instance's lifetime, but in the real deployed system it is currently a no-op:
        every request pays a full recompute. This is not a bug in the cache — it is a deployment-
        model mismatch between "an in-process cache" and "a stateless per-request front controller."
        Fixing it needs a persistence decision (APCu, file-based, or similar) that Module 6's own
        "memory implementation only, no Redis, no external cache" instruction did not resolve — left
        as an explicit, flagged follow-up decision (its own future IDR), not fixed unilaterally here.
      - **Pipeline Audit**: `AnalyticsPipeline` sorts by `CalculatorPriority` (stable for equal
        priority, confirmed by test), wraps context in one shared `CalculatorExecutionContext` per
        run. `capabilities()` is fully implemented and tested on every calculator and the interface,
        but **nothing yet reads it** — no orchestrator filters or gates execution by declared
        Level 1/2 support. Inert-but-correct metadata today, not a defect (nothing in Phase 2's scope
        asked the Pipeline to enforce it yet); flagged as known limitation.
      - **API Audit**: all 6 dashboard routes (`dashboard_summary` plus the 5 new Phase 3 routes)
        are registered behind `StaffAuthMiddleware` — verified directly against
        `public_html/api/index.php`'s route table, no route bypasses it. JSON-only responses
        throughout (`Dmf\Core\Http\Response` has no HTML-rendering path at all). No raw exception
        message construction found anywhere in `app/Analytics`/`app/Action/Dashboard` (`getMessage()`
        does not appear); `Router::dispatch()`'s existing catch-all plus `index.php`'s existing
        500-message sanitization (both pre-existing, Sprint 3) remain the only exception boundary,
        unchanged.
      - **Performance Review**: `AnalyticsReadRepository::findResponsesForAssessment()` is one
        query (a `JOIN`, not N+1); `AssessmentRepository::findLatest()` is one query;
        `DashboardHealthAction` issues 4 queries total (2×`count()`, 1×`findLatest()`,
        1×`findWhere('status','failed')`) — all bounded, no per-row query anywhere. Aggregation and
        Pipeline are both O(n) over already-in-memory collections. The one real finding is the cache
        deployment-model mismatch above.
      - **Security Review (static only, no penetration testing)**: every dashboard route requires
        authentication; the 5 new routes accept no user-controlled input at all (no query-parameter
        support exists — decisions/IDR-011 §2), which is itself a reduced attack surface, not a gap.
        `AnalyticsReadRepository`'s one query uses a bound parameter, no string interpolation. No
        `var_dump`/`print_r`/debug output, no `TODO`/`FIXME` found anywhere in `app/Analytics`,
        `app/Repository`, or `app/Action/Dashboard`.
      - **Documentation Audit — one real, substantive gap found and fixed**: `Release-Notes.md` had
        drifted badly behind actual progress — it still said only T1.1/T1.2 were done and had no
        entry at all for `v0.2.0`–`v0.4.0`, despite all three being real, tagged git releases
        (`v0.2.0-import-validation`, `v0.3.0-import-engine`, `v0.4.0-web-foundation`). Rewrote it
        (→1.1.0) to accurately mark each as **Released** with real content, and added the new
        `v0.5.0` entry. Logged as `00-Project-Overview.md`'s ninth Post-Freeze Amendment (→2.0.10).
        Every other cross-referenced document (`01-PRD.md`, `02-System-Architecture.md`,
        `03-Database-Design.md`, `Business-Flow.md`, `IMPLEMENTATION_GUIDE.md`, RFC-004,
        `PROJECT_BOARD.md`) was already reconciled during the prior RFC-004 alignment pass and
        Sprint 4's own entries — no further contradiction found.
      - **Versioning**: `VERSION` (root) → `0.5.0`. `Release-Notes.md` v0.5.0 entry added
        ("Analytics Engine Complete" — Sprint 4 Phase 1–3 plus RFC-004 alignment).
      - **Git Readiness**: working tree has 14 modified + 34 new paths, all attributable to
        RFC-004 alignment, Sprint 4 Phase 1–3, and this hardening pass — nothing stray. One item
        flagged, not acted on: `Onet/` (33 MB of primary-source O-NET evidence files used for
        RFC-004's research) is untracked and **not** covered by `.gitignore` — left for you to
        decide whether it should be ignored or intentionally committed, since it may be
        institution-specific reference data; not added to any suggested commit below.
      - **Verified for real**: `composer dump-autoload` — 2423 classes (same two pre-existing
        PSR-4 casing notes, unrelated). `vendor/bin/phpunit` — **397/397 tests, 1265 assertions**,
        all passing. `vendor/bin/phpstan analyse` — `[OK] No errors` at level 8.
        `vendor/bin/phpcs --standard=.phpcs.xml` — **132 errors remain, all
        `Generic.Files.LineEndings.InvalidEOLChar`** (the same pre-existing, already-documented
        `core.autocrlf` issue tracked since T2.7, explicitly deferred to its own pass each time it
        has come up; confirmed no new occurrence and no other sniff violation anywhere in `app/` or
        `tests/` after fixing the one genuine finding above).
      - **Known limitations carried forward, unchanged**: no Level 1 or Level 2 Assessment Adapter
        exists yet, so `student_question_responses` is empty in any real deployment and every
        Dashboard endpoint honestly reports zero/empty figures (RFC-004; decisions/IDR-011 §7).
        Dashboard endpoints report on "the latest assessment" only — no query-parameter support in
        `dmf-core`'s `Request` (decisions/IDR-011 §2). The in-memory Dashboard cache does not persist
        across requests (see above).

## Done

---

## Backlog (not yet in a sprint)

Every Phase 2–6 task not yet scoped into a sprint (the five real named templates — `ONET-2569`,
`ONET-2570`, `NT`, `RT`, `School Assessment` — once a real
สทศ file specification is available; T2.3's structural/content validation against the database; the
"resolve current classroom" service T1.5 also names; the PDF parser T2.1 also lists; Sprint 4 Phase
2+ — concrete calculators, aggregation, dashboards, controllers, API, charts, AI) moves onto a board
here once scoped into a sprint — this file only carries active sprints, not the whole roadmap; see
[docs/00-Project-Overview.md §9](docs/00-Project-Overview.md#9-roadmap) and
[docs/Release-Notes.md](docs/Release-Notes.md) for the full multi-release plan.
