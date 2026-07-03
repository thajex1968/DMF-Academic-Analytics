-- DMF Learning Analytics Platform (DLAP) — Verification 001
-- Validates database/seeders/001_assessment_types.sql was applied correctly.
--
-- Read-only. Each SELECT below produces one PASS/FAIL row — run with the
-- mysql CLI and read the output; nothing here modifies data. Same
-- migration/seed strategy, unchanged — no new tooling (see
-- decisions/IDR-007-idempotent-sql-seed-files.md).

-- Check 1: exactly one row exists (per docs/01-PRD.md §6/§25,
-- docs/03-Database-Design.md §4 — the ten reserved codes are NOT seeded).
SELECT
    CASE WHEN COUNT(*) = 1 THEN 'PASS' ELSE 'FAIL' END AS result,
    'assessment_types has exactly 1 row' AS check_description,
    COUNT(*) AS actual_count
FROM assessment_types;

-- Check 2: that one row is ONET, active, with the exact Thai name from
-- docs/03-Database-Design.md §4's v1.0 seed data line.
SELECT
    CASE
        WHEN COUNT(*) = 1 THEN 'PASS'
        ELSE 'FAIL'
    END AS result,
    'ONET row exists, is_active = 1, name_th matches spec exactly' AS check_description,
    COUNT(*) AS actual_count
FROM assessment_types
WHERE code = 'ONET'
  AND is_active = 1
  AND name_th = 'การทดสอบทางการศึกษาระดับชาติขั้นพื้นฐาน (O-NET)';

-- Check 3: none of the ten reserved codes are present — guards against a
-- future seed change accidentally activating scope not yet approved
-- (docs/01-PRD.md §21 — Approval Flow).
SELECT
    CASE WHEN COUNT(*) = 0 THEN 'PASS' ELSE 'FAIL' END AS result,
    'none of the ten reserved codes are seeded' AS check_description,
    COUNT(*) AS actual_count
FROM assessment_types
WHERE code IN (
    'PRE_TEST', 'MID_TEST', 'POST_TEST', 'NT', 'RT', 'LAS',
    'CLASSROOM_ASSESSMENT', 'READING_ASSESSMENT', 'WRITING_ASSESSMENT', 'COMPETENCY_ASSESSMENT'
);
