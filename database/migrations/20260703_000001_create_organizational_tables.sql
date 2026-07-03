-- DMF Learning Analytics Platform (DLAP) — Migration 000001
-- Organizational tables (docs/03-Database-Design.md §3), plus `subjects`
-- (§5 — Standards Catalogue) relocated here because `assessments` (migration
-- 000002) has a foreign key to it; see the migration-ordering discussion this
-- file resolves. Source of truth for every column/key/index below:
-- database/schema.sql (the full reference schema this file is one slice of).

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
