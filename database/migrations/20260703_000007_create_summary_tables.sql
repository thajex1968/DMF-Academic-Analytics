-- DMF Learning Analytics Platform (DLAP) — Migration 000007
-- Aggregation & Materialized Summaries tables (docs/03-Database-Design.md §9).
-- Depends on: assessment_types (000002), students (000001),
-- learning_indicators (000003), questions (000004).
-- Source of truth: database/schema.sql.

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
