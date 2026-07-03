-- DMF Learning Analytics Platform (DLAP) — Migration 000003
-- Standards Catalogue tables (docs/03-Database-Design.md §5) — the
-- สาระ → มาตรฐาน → ตัวชี้วัด hierarchy. `subjects` (also §5) was relocated to
-- migration 000001 — see that file's header comment.
-- Depends on: subjects (migration 000001).
-- Source of truth: database/schema.sql.

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
