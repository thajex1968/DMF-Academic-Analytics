-- DMF Learning Analytics Platform (DLAP) — Seeder 002
-- subjects: v1.0 foundational master data.
--
-- Seeds exactly the four subjects O-NET Grade 6 v1.0 covers, per
-- docs/01-PRD.md §6 ("Import of official O-NET score exports... for Grade 6,
-- subjects ภาษาไทย, คณิตศาสตร์, วิทยาศาสตร์, ภาษาอังกฤษ") — matching the same
-- four codes used as examples in docs/03-Database-Design.md §5 and
-- docs/Data-Dictionary.md (THAI, MATH, SCI, ENG).
--
-- Idempotent: safe to re-run — see decisions/IDR-007-idempotent-sql-seed-files.md.
--
-- If running manually via the mysql CLI, pass --default-character-set=utf8mb4
-- explicitly — without it, the client's default connection charset can
-- mis-transcode the Thai text below on INSERT (verified during T1.4: rows
-- came back corrupted without this flag, byte-correct with it).

INSERT INTO subjects (subject_code, subject_name_th, is_active) VALUES
    ('THAI', 'ภาษาไทย', 1),
    ('MATH', 'คณิตศาสตร์', 1),
    ('SCI', 'วิทยาศาสตร์', 1),
    ('ENG', 'ภาษาอังกฤษ', 1)
ON DUPLICATE KEY UPDATE
    subject_name_th = VALUES(subject_name_th),
    is_active = VALUES(is_active);
