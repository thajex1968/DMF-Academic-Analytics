-- DMF Learning Analytics Platform (DLAP) — Migration 000004
-- Questions & Item Mapping tables (docs/03-Database-Design.md §6).
-- Depends on: assessments (000002), learning_indicators (000003).
-- Source of truth: database/schema.sql.

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
