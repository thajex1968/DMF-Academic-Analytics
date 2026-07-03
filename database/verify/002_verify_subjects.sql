-- DMF Learning Analytics Platform (DLAP) — Verification 002
-- Validates database/seeders/002_subjects.sql was applied correctly.
--
-- Read-only. Each SELECT below produces one PASS/FAIL row — run with the
-- mysql CLI and read the output; nothing here modifies data. Same
-- migration/seed strategy, unchanged — no new tooling (see
-- decisions/IDR-007-idempotent-sql-seed-files.md).

-- Check 1: exactly four rows exist, matching docs/01-PRD.md §6's exact v1.0
-- subject list (ภาษาไทย, คณิตศาสตร์, วิทยาศาสตร์, ภาษาอังกฤษ).
SELECT
    CASE WHEN COUNT(*) = 4 THEN 'PASS' ELSE 'FAIL' END AS result,
    'subjects has exactly 4 rows' AS check_description,
    COUNT(*) AS actual_count
FROM subjects;

-- Check 2: all four expected codes are present, active, with the exact
-- Thai names from docs/01-PRD.md §6.
SELECT
    CASE WHEN COUNT(*) = 4 THEN 'PASS' ELSE 'FAIL' END AS result,
    'THAI/MATH/SCI/ENG all present, active, with correct Thai names' AS check_description,
    COUNT(*) AS actual_count
FROM subjects
WHERE is_active = 1
  AND (
      (subject_code = 'THAI' AND subject_name_th = 'ภาษาไทย')
      OR (subject_code = 'MATH' AND subject_name_th = 'คณิตศาสตร์')
      OR (subject_code = 'SCI'  AND subject_name_th = 'วิทยาศาสตร์')
      OR (subject_code = 'ENG'  AND subject_name_th = 'ภาษาอังกฤษ')
  );

-- Check 3: no subject_code outside the expected four exists — guards
-- against seed drift.
SELECT
    CASE WHEN COUNT(*) = 0 THEN 'PASS' ELSE 'FAIL' END AS result,
    'no unexpected subject_code beyond THAI/MATH/SCI/ENG' AS check_description,
    COUNT(*) AS actual_count
FROM subjects
WHERE subject_code NOT IN ('THAI', 'MATH', 'SCI', 'ENG');
