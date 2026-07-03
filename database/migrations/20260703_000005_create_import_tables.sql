-- DMF Learning Analytics Platform (DLAP) — Migration 000005
-- Import Pipeline tables (docs/03-Database-Design.md §7).
-- Depends on: schools/staff_users (000001), assessments (000002).
-- Source of truth: database/schema.sql.

CREATE TABLE import_jobs (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id      INT UNSIGNED    NOT NULL,
    assessment_id  INT UNSIGNED    NOT NULL,
    file_path      VARCHAR(500)    NOT NULL,
    file_type      ENUM('pdf','xlsx','csv') NOT NULL,
    status         ENUM('queued','processing','committed','failed') NOT NULL DEFAULT 'queued',
    error_detail   TEXT            NULL DEFAULT NULL,
    uploaded_by    INT UNSIGNED    NOT NULL,
    created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_import_jobs_school_assessment_file (school_id, assessment_id, file_path),
    KEY idx_import_jobs_status (status),
    KEY idx_import_jobs_school_assessment (school_id, assessment_id),
    CONSTRAINT fk_import_jobs_school FOREIGN KEY (school_id) REFERENCES schools (id),
    CONSTRAINT fk_import_jobs_assessment FOREIGN KEY (assessment_id) REFERENCES assessments (id),
    CONSTRAINT fk_import_jobs_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES staff_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- import_logs — append-only audit trail per import_job_id (FR-008).
CREATE TABLE import_logs (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    import_job_id   BIGINT UNSIGNED NOT NULL,
    event           VARCHAR(50)     NOT NULL,
    message         TEXT            NULL DEFAULT NULL,
    actor_id        INT UNSIGNED    NULL DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_import_logs_import_job_id (import_job_id),
    CONSTRAINT fk_import_logs_import_job FOREIGN KEY (import_job_id) REFERENCES import_jobs (id),
    CONSTRAINT fk_import_logs_actor FOREIGN KEY (actor_id) REFERENCES staff_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
