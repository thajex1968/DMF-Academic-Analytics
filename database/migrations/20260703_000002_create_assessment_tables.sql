-- DMF Learning Analytics Platform (DLAP) — Migration 000002
-- Assessment Framework tables (docs/03-Database-Design.md §4).
-- Depends on: schools/subjects (migration 000001).
-- Source of truth: database/schema.sql.

CREATE TABLE assessment_types (
    id        TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code      VARCHAR(30)      NOT NULL,
    name_th   VARCHAR(150)     NOT NULL,
    is_active TINYINT(1)       NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assessment_types_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- row, ONET. Seeding itself is Task T1.4 ("Seeder"), not part of this migration.
