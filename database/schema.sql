-- DMF Learning Analytics Platform (DLAP) — database `dmf_academic`
--
-- Reference schema: the full DDL for every table, matching docs/03-Database-Design.md
-- exactly (Revision 2.0.1). This file is the canonical, single-file schema reference —
-- see database/migrations/ for the incremental, timestamped files this is actually
-- applied through (docs/03-Database-Design.md §16 — Migration Strategy).
--
-- Task: IMPLEMENTATION_GUIDE.md T1.3 ("Database"). Character set / engine / session
-- timezone conventions: docs/03-Database-Design.md §1 (Design Principles). The session
-- timezone (`SET time_zone = '+07:00'`) is set by the application connection layer
-- (dmf-core's Connection), not baked into this file, since it is a per-session setting,
-- not schema DDL.

CREATE DATABASE IF NOT EXISTS dmf_academic
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE dmf_academic;

-- =============================================================================
-- 1. Organizational (docs/03-Database-Design.md §3)
-- =============================================================================

CREATE TABLE schools (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_code  VARCHAR(10)  NOT NULL,
    name_th      VARCHAR(255) NOT NULL,
    province     VARCHAR(100) NOT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_schools_school_code (school_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subjects (
    subject_code     VARCHAR(10)  NOT NULL,
    subject_name_th  VARCHAR(100) NOT NULL,
    is_active        TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (subject_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE assessment_types (
    id        TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code      VARCHAR(30)      NOT NULL,
    name_th   VARCHAR(150)     NOT NULL,
    is_active TINYINT(1)       NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assessment_types_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE classrooms (
    id             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    school_id      INT UNSIGNED     NOT NULL,
    grade_level    TINYINT UNSIGNED NOT NULL,
    room_label     VARCHAR(20)      NOT NULL,
    academic_year  INT UNSIGNED     NOT NULL,
    created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_classrooms_school_room_year (school_id, room_label, academic_year),
    CONSTRAINT fk_classrooms_school FOREIGN KEY (school_id) REFERENCES schools (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE staff_users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name  VARCHAR(255) NOT NULL,
    role          ENUM('teacher','director','admin','inspector') NOT NULL,
    school_id     INT UNSIGNED NOT NULL,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_staff_users_username (username),
    CONSTRAINT fk_staff_users_school FOREIGN KEY (school_id) REFERENCES schools (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE students (
    student_id    VARCHAR(20)  NOT NULL,
    classroom_id  INT UNSIGNED NOT NULL,
    full_name     VARCHAR(255) NOT NULL,
    national_id   CHAR(13)     NULL DEFAULT NULL,
    status        VARCHAR(20)  NOT NULL DEFAULT 'active',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id),
    UNIQUE KEY uq_students_national_id (national_id),
    KEY idx_students_classroom_id (classroom_id),
    CONSTRAINT fk_students_classroom FOREIGN KEY (classroom_id) REFERENCES classrooms (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- student_enrollments — the authoritative grade/classroom history per student per
-- academic year; students.classroom_id is a denormalized pointer to the most recent
-- row here (docs/03-Database-Design.md §11).
CREATE TABLE student_enrollments (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id         VARCHAR(20)     NOT NULL,
    school_id          INT UNSIGNED    NOT NULL,
    classroom_id       INT UNSIGNED    NOT NULL,
    grade_level        TINYINT UNSIGNED NOT NULL,
    academic_year      INT UNSIGNED    NOT NULL,
    enrollment_status  ENUM('active','transferred','graduated','repeated') NOT NULL DEFAULT 'active',
    created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_enrollments_student_year (student_id, academic_year),
    KEY idx_student_enrollments_student_id (student_id),
    CONSTRAINT fk_student_enrollments_student FOREIGN KEY (student_id) REFERENCES students (student_id),
    CONSTRAINT fk_student_enrollments_school FOREIGN KEY (school_id) REFERENCES schools (id),
    CONSTRAINT fk_student_enrollments_classroom FOREIGN KEY (classroom_id) REFERENCES classrooms (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teacher_classrooms (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    staff_user_id  INT UNSIGNED NOT NULL,
    classroom_id   INT UNSIGNED NOT NULL,
    is_current     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_teacher_classrooms_staff_classroom (staff_user_id, classroom_id),
    CONSTRAINT fk_teacher_classrooms_staff_user FOREIGN KEY (staff_user_id) REFERENCES staff_users (id),
    CONSTRAINT fk_teacher_classrooms_classroom FOREIGN KEY (classroom_id) REFERENCES classrooms (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. Assessment Framework (docs/03-Database-Design.md §4)
-- =============================================================================

CREATE TABLE assessments (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    assessment_type_id   TINYINT UNSIGNED NOT NULL,
    subject_code         VARCHAR(10)      NOT NULL,
    grade_level          TINYINT UNSIGNED NOT NULL,
    academic_year        INT UNSIGNED     NOT NULL,
    name_th              VARCHAR(255)     NOT NULL,
    created_at           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assessments_type_subject_year (assessment_type_id, subject_code, academic_year),
    CONSTRAINT fk_assessments_assessment_type FOREIGN KEY (assessment_type_id) REFERENCES assessment_types (id),
    CONSTRAINT fk_assessments_subject FOREIGN KEY (subject_code) REFERENCES subjects (subject_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- v1.0 seed data (docs/03-Database-Design.md §4): exactly one active assessment_types
-- row, ONET. Seeding itself is Task T1.4 ("Seeder"), not part of this schema file.

-- =============================================================================
-- 3. Standards Catalogue (docs/03-Database-Design.md §5)
-- =============================================================================

CREATE TABLE learning_strands (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject_code     VARCHAR(10)  NOT NULL,
    strand_code      VARCHAR(20)  NOT NULL,
    strand_name_th   VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_learning_strands_subject_strand (subject_code, strand_code),
    CONSTRAINT fk_learning_strands_subject FOREIGN KEY (subject_code) REFERENCES subjects (subject_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE learning_standards (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    strand_id         INT UNSIGNED NOT NULL,
    standard_code     VARCHAR(20)  NOT NULL,
    standard_name_th  TEXT         NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_learning_standards_strand_standard (strand_id, standard_code),
    CONSTRAINT fk_learning_standards_strand FOREIGN KEY (strand_id) REFERENCES learning_strands (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE learning_indicators (
    id                    INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    standard_id           INT UNSIGNED     NOT NULL,
    indicator_code        VARCHAR(30)      NOT NULL,
    indicator_name_th     TEXT             NOT NULL,
    grade_level           TINYINT UNSIGNED NOT NULL,
    curriculum_revision   VARCHAR(10)      NOT NULL DEFAULT '2560',
    PRIMARY KEY (id),
    UNIQUE KEY uq_learning_indicators_standard_code_revision (standard_id, indicator_code, curriculum_revision),
    CONSTRAINT fk_learning_indicators_standard FOREIGN KEY (standard_id) REFERENCES learning_standards (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 4. Questions & Item Mapping (docs/03-Database-Design.md §6)
-- =============================================================================

CREATE TABLE questions (
    id                     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    assessment_id          INT UNSIGNED     NOT NULL,
    item_number            SMALLINT UNSIGNED NOT NULL,
    primary_indicator_id   INT UNSIGNED     NOT NULL,
    correct_choice         CHAR(1)          NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_questions_assessment_item (assessment_id, item_number),
    CONSTRAINT fk_questions_assessment FOREIGN KEY (assessment_id) REFERENCES assessments (id),
    CONSTRAINT fk_questions_primary_indicator FOREIGN KEY (primary_indicator_id) REFERENCES learning_indicators (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE question_secondary_indicators (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    question_id   INT UNSIGNED NOT NULL,
    indicator_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_question_secondary_indicators_question_indicator (question_id, indicator_id),
    CONSTRAINT fk_qsi_question FOREIGN KEY (question_id) REFERENCES questions (id),
    CONSTRAINT fk_qsi_indicator FOREIGN KEY (indicator_id) REFERENCES learning_indicators (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 5. Import Pipeline (docs/03-Database-Design.md §7)
-- =============================================================================

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

-- =============================================================================
-- 6. Scores & Responses (docs/03-Database-Design.md §8)
-- =============================================================================

-- Committed score/response rows are immutable (docs/03-Database-Design.md §13) — a
-- correction is a new import_job whose rows supersede the prior ones logically, never
-- an UPDATE/DELETE of a committed row.
CREATE TABLE student_scores (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id     VARCHAR(20)     NOT NULL,
    assessment_id  INT UNSIGNED    NOT NULL,
    score          DECIMAL(5,2)    NOT NULL,
    import_job_id  BIGINT UNSIGNED NOT NULL,
    created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_scores_student_assessment (student_id, assessment_id),
    KEY idx_student_scores_assessment_id (assessment_id),
    CONSTRAINT fk_student_scores_student FOREIGN KEY (student_id) REFERENCES students (student_id),
    CONSTRAINT fk_student_scores_assessment FOREIGN KEY (assessment_id) REFERENCES assessments (id),
    CONSTRAINT fk_student_scores_import_job FOREIGN KEY (import_job_id) REFERENCES import_jobs (id),
    CONSTRAINT chk_student_scores_score_range CHECK (score BETWEEN 0.00 AND 100.00)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_question_responses (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id        VARCHAR(20)     NOT NULL,
    question_id       INT UNSIGNED    NOT NULL,
    selected_choice   CHAR(1)         NULL DEFAULT NULL,
    is_correct        TINYINT(1)      NOT NULL,
    import_job_id     BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sqr_student_question (student_id, question_id),
    KEY idx_sqr_question_id (question_id),
    CONSTRAINT fk_sqr_student FOREIGN KEY (student_id) REFERENCES students (student_id),
    CONSTRAINT fk_sqr_question FOREIGN KEY (question_id) REFERENCES questions (id),
    CONSTRAINT fk_sqr_import_job FOREIGN KEY (import_job_id) REFERENCES import_jobs (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 7. Aggregation & Materialized Summaries (docs/03-Database-Design.md §9)
-- =============================================================================

-- scope_id is polymorphic (classroom_id for scope='classroom'; school_id for
-- scope='grade'/'school') per docs/03-Database-Design.md §9 — no FK is declared on it
-- there, so none is added here; the same applies to question_analysis.scope_id below.
CREATE TABLE standard_performance_summary (
    id                   BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    assessment_type_id   TINYINT UNSIGNED NOT NULL,
    scope                ENUM('classroom','grade','school') NOT NULL,
    scope_id             INT UNSIGNED     NOT NULL,
    indicator_id         INT UNSIGNED     NOT NULL,
    academic_year        INT UNSIGNED     NOT NULL,
    student_count        INT UNSIGNED     NOT NULL,
    percent_correct      DECIMAL(5,2)     NOT NULL,
    last_computed_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sps_type_scope_indicator_year (assessment_type_id, scope, scope_id, indicator_id, academic_year),
    KEY idx_sps_indicator_year (indicator_id, academic_year),
    CONSTRAINT fk_sps_assessment_type FOREIGN KEY (assessment_type_id) REFERENCES assessment_types (id),
    CONSTRAINT fk_sps_indicator FOREIGN KEY (indicator_id) REFERENCES learning_indicators (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- student_standard_mastery — schema-ready, NOT populated in v1.0 (YAGNI; see
-- docs/03-Database-Design.md §9's status note). The table exists now so the data model
-- does not change again when the per-student report phase ships.
CREATE TABLE student_standard_mastery (
    id                   BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    student_id           VARCHAR(20)      NOT NULL,
    indicator_id         INT UNSIGNED     NOT NULL,
    assessment_type_id   TINYINT UNSIGNED NOT NULL,
    academic_year        INT UNSIGNED     NOT NULL,
    grade_level          TINYINT UNSIGNED NOT NULL,
    question_count       INT UNSIGNED     NOT NULL,
    correct_count        INT UNSIGNED     NOT NULL,
    percent_correct      DECIMAL(5,2)     NOT NULL,
    last_computed_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ssm_student_indicator_type_year (student_id, indicator_id, assessment_type_id, academic_year),
    KEY idx_ssm_student_indicator (student_id, indicator_id),
    CONSTRAINT fk_ssm_student FOREIGN KEY (student_id) REFERENCES students (student_id),
    CONSTRAINT fk_ssm_indicator FOREIGN KEY (indicator_id) REFERENCES learning_indicators (id),
    CONSTRAINT fk_ssm_assessment_type FOREIGN KEY (assessment_type_id) REFERENCES assessment_types (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE question_analysis (
    id                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    question_id                 INT UNSIGNED    NOT NULL,
    scope                       ENUM('classroom','school') NOT NULL,
    scope_id                    INT UNSIGNED    NOT NULL,
    difficulty_index            DECIMAL(4,3)    NOT NULL,
    discrimination_index        DECIMAL(4,3)    NOT NULL,
    distractor_frequency_json   JSON            NOT NULL,
    last_computed_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_question_analysis_question_scope (question_id, scope, scope_id),
    CONSTRAINT fk_question_analysis_question FOREIGN KEY (question_id) REFERENCES questions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 8. Reporting, Diagnostics & Platform (docs/03-Database-Design.md §10)
-- =============================================================================

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

-- =============================================================================
-- 9. Migration Tracking (docs/03-Database-Design.md §16)
-- =============================================================================

CREATE TABLE schema_migrations (
    version      VARCHAR(20) NOT NULL,
    applied_at   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
