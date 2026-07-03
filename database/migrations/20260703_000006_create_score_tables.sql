-- DMF Learning Analytics Platform (DLAP) — Migration 000006
-- Scores & Responses tables (docs/03-Database-Design.md §8).
-- Depends on: students (000001), assessments (000002), questions (000004),
-- import_jobs (000005).
-- Source of truth: database/schema.sql.

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
