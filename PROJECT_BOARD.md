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
this phase is now built. Per `IMPLEMENTATION_GUIDE.md` v1.1.0 (see its own Revision History), T2.4
is now "Import Session & Error Reporting" — the task the entry below was originally reviewed under
before the frozen doc was amended to match it; Normalization (FR-009) moved to T2.5, built and
awaiting review below. Duplicate Detection + Audit Trail (FR-007/FR-008) moved to T2.6, now built
and Approved below. T2.7 (cron runner + commit transaction) is also now built and awaiting review
below.

## Todo

## In Progress

## Review
- [ ] Normalization — T2.5 (Item-to-Indicator Normalization, FR-009 —
      [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task)) — given directly by instruction.
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
- [ ] Cron Runner + Commit Transaction — T2.7 (FR-006's "no partial commits" rule —
      [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md#2-task)) — the commit-transaction half was
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

## Backlog (not yet in a sprint)

The rest of [IMPLEMENTATION_GUIDE.md §2](IMPLEMENTATION_GUIDE.md#2-task)'s Phase 1 tasks (T1.6–T1.7:
Auth, Dashboard shell — plus the "resolve current classroom" service T1.5 also names, deliberately
not built in this pass, see Sprint 1's Done entry above) and every Phase 2–6 task not yet scoped
into a sprint (the five real named templates — `ONET-2569`, `ONET-2570`, `NT`, `RT`, `School
Assessment` — once a real สทศ file specification is available; T2.3's structural/content
validation against the database; T2.7's cron runner + commit transaction (Score Import); the PDF
parser T2.1 also lists; all of Phase 3+) move onto a board here once Sprint 2 is done — this file
only carries the current and next sprint, not the whole roadmap; see
[docs/00-Project-Overview.md §9](docs/00-Project-Overview.md#9-roadmap) and
[docs/Release-Notes.md](docs/Release-Notes.md) for the full multi-release plan.
