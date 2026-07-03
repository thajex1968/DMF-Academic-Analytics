-- DMF Learning Analytics Platform (DLAP) — Seeder 001
-- assessment_types: v1.0 foundational master data.
--
-- Seeds exactly ONE row (ONET, active) — NOT the ten reserved codes. Per
-- docs/01-PRD.md §6 ("only ONET is seeded and active in v1.0") and §25
-- ("Future Expansion: ...activating the ten reserved assessment types"), and
-- docs/03-Database-Design.md §4 ("exactly one row... the other ten codes...
-- are not seeded in v1.0"). Activating a reserved code later goes through the
-- Approval Flow (docs/01-PRD.md §21), not a seed file.
--
-- Idempotent: safe to re-run — see decisions/IDR-007-idempotent-sql-seed-files.md.
--
-- If running manually via the mysql CLI, pass --default-character-set=utf8mb4
-- explicitly — without it, the client's default connection charset can
-- mis-transcode the Thai text below on INSERT (verified during T1.4: rows
-- came back corrupted without this flag, byte-correct with it).

INSERT INTO assessment_types (code, name_th, is_active) VALUES
    ('ONET', 'การทดสอบทางการศึกษาระดับชาติขั้นพื้นฐาน (O-NET)', 1)
ON DUPLICATE KEY UPDATE
    name_th = VALUES(name_th),
    is_active = VALUES(is_active);
