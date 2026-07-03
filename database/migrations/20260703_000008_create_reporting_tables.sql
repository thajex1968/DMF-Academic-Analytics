-- DMF Learning Analytics Platform (DLAP) — Migration 000008
-- Reporting, Diagnostics & Platform tables (docs/03-Database-Design.md §10).
-- Depends on: learning_indicators (000003), classrooms/schools/staff_users (000001).
-- Source of truth: database/schema.sql.

CREATE TABLE learning_contents (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    indicator_id   INT UNSIGNED NOT NULL,
    title          VARCHAR(255) NOT NULL,
    resource_type  ENUM('worksheet','video','lesson_plan','external_link') NOT NULL,
    url_or_path    VARCHAR(500) NOT NULL,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    CONSTRAINT fk_learning_contents_indicator FOREIGN KEY (indicator_id) REFERENCES learning_indicators (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_recommendations (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    classroom_id   INT UNSIGNED    NOT NULL,
    academic_year  INT UNSIGNED    NOT NULL,
    source         ENUM('rule_based','llm') NOT NULL,
    narrative      TEXT            NULL DEFAULT NULL,
    generated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_ai_recommendations_classroom FOREIGN KEY (classroom_id) REFERENCES classrooms (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE scheduled_reports (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id         INT UNSIGNED NOT NULL,
    report_type       ENUM('teacher_pdf','school_excel') NOT NULL,
    recipient_emails  TEXT         NOT NULL,
    cron_expression   VARCHAR(50)  NOT NULL,
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_scheduled_reports_school FOREIGN KEY (school_id) REFERENCES schools (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE report_exports (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scheduled_report_id   INT UNSIGNED    NULL DEFAULT NULL,
    generated_by          INT UNSIGNED    NULL DEFAULT NULL,
    file_path             VARCHAR(500)    NOT NULL,
    status                ENUM('generated','sent','failed') NOT NULL DEFAULT 'generated',
    created_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_report_exports_scheduled_report FOREIGN KEY (scheduled_report_id) REFERENCES scheduled_reports (id),
    CONSTRAINT fk_report_exports_generated_by FOREIGN KEY (generated_by) REFERENCES staff_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_id     INT UNSIGNED    NULL DEFAULT NULL,
    action       VARCHAR(100)    NOT NULL,
    entity_type  VARCHAR(100)    NOT NULL,
    entity_id    VARCHAR(50)     NOT NULL,
    detail_json  JSON            NULL DEFAULT NULL,
    ip_address   VARCHAR(45)     NOT NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_id) REFERENCES staff_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- login_rate_limits — backing store for dmf-core's Auth\RateLimiter on shared hosting
-- (no Redis/APCu assumed), docs/03-Database-Design.md §10.
CREATE TABLE login_rate_limits (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username         VARCHAR(100)    NOT NULL,
    failed_attempts  INT UNSIGNED    NOT NULL DEFAULT 0,
    locked_until     DATETIME        NULL DEFAULT NULL,
    updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_login_rate_limits_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
