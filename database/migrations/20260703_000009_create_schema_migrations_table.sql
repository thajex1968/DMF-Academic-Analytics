-- DMF Learning Analytics Platform (DLAP) — Migration 000009
-- Migration tracking table (docs/03-Database-Design.md §16).
-- No dependencies. Per decisions/IDR-005-database-connection-strategy.md, the
-- Migration Engine (Module 2, not yet built) is what records a version row here
-- after each migration runs — this file only creates the table itself.
-- Source of truth: database/schema.sql.

CREATE TABLE schema_migrations (
    version      VARCHAR(20) NOT NULL,
    applied_at   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
