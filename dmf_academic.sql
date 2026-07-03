-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 03, 2026 at 09:20 PM
-- Server version: 10.6.27-MariaDB-log
-- PHP Version: 8.3.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dmf_academic`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_recommendations`
--

CREATE TABLE `ai_recommendations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `classroom_id` int(10) UNSIGNED NOT NULL,
  `academic_year` int(10) UNSIGNED NOT NULL,
  `source` enum('rule_based','llm') NOT NULL,
  `narrative` text DEFAULT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(10) UNSIGNED NOT NULL,
  `assessment_type_id` tinyint(3) UNSIGNED NOT NULL,
  `subject_code` varchar(10) NOT NULL,
  `grade_level` tinyint(3) UNSIGNED NOT NULL,
  `academic_year` int(10) UNSIGNED NOT NULL,
  `name_th` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_types`
--

CREATE TABLE `assessment_types` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name_th` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actor_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` varchar(50) NOT NULL,
  `detail_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detail_json`)),
  `ip_address` varchar(45) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classrooms`
--

CREATE TABLE `classrooms` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `grade_level` tinyint(3) UNSIGNED NOT NULL,
  `room_label` varchar(20) NOT NULL,
  `academic_year` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `import_jobs`
--

CREATE TABLE `import_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `assessment_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('pdf','xlsx','csv') NOT NULL,
  `status` enum('queued','processing','committed','failed') NOT NULL DEFAULT 'queued',
  `error_detail` text DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `import_logs`
--

CREATE TABLE `import_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `import_job_id` bigint(20) UNSIGNED NOT NULL,
  `event` varchar(50) NOT NULL,
  `message` text DEFAULT NULL,
  `actor_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_contents`
--

CREATE TABLE `learning_contents` (
  `id` int(10) UNSIGNED NOT NULL,
  `indicator_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `resource_type` enum('worksheet','video','lesson_plan','external_link') NOT NULL,
  `url_or_path` varchar(500) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_indicators`
--

CREATE TABLE `learning_indicators` (
  `id` int(10) UNSIGNED NOT NULL,
  `standard_id` int(10) UNSIGNED NOT NULL,
  `indicator_code` varchar(30) NOT NULL,
  `indicator_name_th` text NOT NULL,
  `grade_level` tinyint(3) UNSIGNED NOT NULL,
  `curriculum_revision` varchar(10) NOT NULL DEFAULT '2560'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_standards`
--

CREATE TABLE `learning_standards` (
  `id` int(10) UNSIGNED NOT NULL,
  `strand_id` int(10) UNSIGNED NOT NULL,
  `standard_code` varchar(20) NOT NULL,
  `standard_name_th` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_strands`
--

CREATE TABLE `learning_strands` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject_code` varchar(10) NOT NULL,
  `strand_code` varchar(20) NOT NULL,
  `strand_name_th` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_rate_limits`
--

CREATE TABLE `login_rate_limits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `failed_attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `assessment_id` int(10) UNSIGNED NOT NULL,
  `item_number` smallint(5) UNSIGNED NOT NULL,
  `primary_indicator_id` int(10) UNSIGNED NOT NULL,
  `correct_choice` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_analysis`
--

CREATE TABLE `question_analysis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `scope` enum('classroom','school') NOT NULL,
  `scope_id` int(10) UNSIGNED NOT NULL,
  `difficulty_index` decimal(4,3) NOT NULL,
  `discrimination_index` decimal(4,3) NOT NULL,
  `distractor_frequency_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`distractor_frequency_json`)),
  `last_computed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_secondary_indicators`
--

CREATE TABLE `question_secondary_indicators` (
  `id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `indicator_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_exports`
--

CREATE TABLE `report_exports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scheduled_report_id` int(10) UNSIGNED DEFAULT NULL,
  `generated_by` int(10) UNSIGNED DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `status` enum('generated','sent','failed') NOT NULL DEFAULT 'generated',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_reports`
--

CREATE TABLE `scheduled_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `report_type` enum('teacher_pdf','school_excel') NOT NULL,
  `recipient_emails` text NOT NULL,
  `cron_expression` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `version` varchar(20) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_code` varchar(10) NOT NULL,
  `name_th` varchar(255) NOT NULL,
  `province` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_users`
--

CREATE TABLE `staff_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `role` enum('teacher','director','admin','inspector') NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `standard_performance_summary`
--

CREATE TABLE `standard_performance_summary` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_type_id` tinyint(3) UNSIGNED NOT NULL,
  `scope` enum('classroom','grade','school') NOT NULL,
  `scope_id` int(10) UNSIGNED NOT NULL,
  `indicator_id` int(10) UNSIGNED NOT NULL,
  `academic_year` int(10) UNSIGNED NOT NULL,
  `student_count` int(10) UNSIGNED NOT NULL,
  `percent_correct` decimal(5,2) NOT NULL,
  `last_computed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` varchar(20) NOT NULL,
  `classroom_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `national_id` char(13) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `classroom_id` int(10) UNSIGNED NOT NULL,
  `grade_level` tinyint(3) UNSIGNED NOT NULL,
  `academic_year` int(10) UNSIGNED NOT NULL,
  `enrollment_status` enum('active','transferred','graduated','repeated') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_question_responses`
--

CREATE TABLE `student_question_responses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `selected_choice` char(1) DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `import_job_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_scores`
--

CREATE TABLE `student_scores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `assessment_id` int(10) UNSIGNED NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `import_job_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `student_standard_mastery`
--

CREATE TABLE `student_standard_mastery` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `indicator_id` int(10) UNSIGNED NOT NULL,
  `assessment_type_id` tinyint(3) UNSIGNED NOT NULL,
  `academic_year` int(10) UNSIGNED NOT NULL,
  `grade_level` tinyint(3) UNSIGNED NOT NULL,
  `question_count` int(10) UNSIGNED NOT NULL,
  `correct_count` int(10) UNSIGNED NOT NULL,
  `percent_correct` decimal(5,2) NOT NULL,
  `last_computed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_code` varchar(10) NOT NULL,
  `subject_name_th` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_classrooms`
--

CREATE TABLE `teacher_classrooms` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_user_id` int(10) UNSIGNED NOT NULL,
  `classroom_id` int(10) UNSIGNED NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ai_recommendations_classroom` (`classroom_id`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_assessments_type_subject_year` (`assessment_type_id`,`subject_code`,`academic_year`),
  ADD KEY `fk_assessments_subject` (`subject_code`);

--
-- Indexes for table `assessment_types`
--
ALTER TABLE `assessment_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_assessment_types_code` (`code`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_audit_logs_actor` (`actor_id`);

--
-- Indexes for table `classrooms`
--
ALTER TABLE `classrooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_classrooms_school_room_year` (`school_id`,`room_label`,`academic_year`);

--
-- Indexes for table `import_jobs`
--
ALTER TABLE `import_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_import_jobs_school_assessment_file` (`school_id`,`assessment_id`,`file_path`),
  ADD KEY `idx_import_jobs_status` (`status`),
  ADD KEY `idx_import_jobs_school_assessment` (`school_id`,`assessment_id`),
  ADD KEY `fk_import_jobs_assessment` (`assessment_id`),
  ADD KEY `fk_import_jobs_uploaded_by` (`uploaded_by`);

--
-- Indexes for table `import_logs`
--
ALTER TABLE `import_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_import_logs_import_job_id` (`import_job_id`),
  ADD KEY `fk_import_logs_actor` (`actor_id`);

--
-- Indexes for table `learning_contents`
--
ALTER TABLE `learning_contents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_learning_contents_indicator` (`indicator_id`);

--
-- Indexes for table `learning_indicators`
--
ALTER TABLE `learning_indicators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_learning_indicators_standard_code_revision` (`standard_id`,`indicator_code`,`curriculum_revision`);

--
-- Indexes for table `learning_standards`
--
ALTER TABLE `learning_standards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_learning_standards_strand_standard` (`strand_id`,`standard_code`);

--
-- Indexes for table `learning_strands`
--
ALTER TABLE `learning_strands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_learning_strands_subject_strand` (`subject_code`,`strand_code`);

--
-- Indexes for table `login_rate_limits`
--
ALTER TABLE `login_rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_login_rate_limits_username` (`username`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_questions_assessment_item` (`assessment_id`,`item_number`),
  ADD KEY `fk_questions_primary_indicator` (`primary_indicator_id`);

--
-- Indexes for table `question_analysis`
--
ALTER TABLE `question_analysis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_question_analysis_question_scope` (`question_id`,`scope`,`scope_id`);

--
-- Indexes for table `question_secondary_indicators`
--
ALTER TABLE `question_secondary_indicators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_question_secondary_indicators_question_indicator` (`question_id`,`indicator_id`),
  ADD KEY `fk_qsi_indicator` (`indicator_id`);

--
-- Indexes for table `report_exports`
--
ALTER TABLE `report_exports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_report_exports_scheduled_report` (`scheduled_report_id`),
  ADD KEY `fk_report_exports_generated_by` (`generated_by`);

--
-- Indexes for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_scheduled_reports_school` (`school_id`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_schools_school_code` (`school_code`);

--
-- Indexes for table `staff_users`
--
ALTER TABLE `staff_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_staff_users_username` (`username`),
  ADD KEY `fk_staff_users_school` (`school_id`);

--
-- Indexes for table `standard_performance_summary`
--
ALTER TABLE `standard_performance_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sps_type_scope_indicator_year` (`assessment_type_id`,`scope`,`scope_id`,`indicator_id`,`academic_year`),
  ADD KEY `idx_sps_indicator_year` (`indicator_id`,`academic_year`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `uq_students_national_id` (`national_id`),
  ADD KEY `idx_students_classroom_id` (`classroom_id`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_enrollments_student_year` (`student_id`,`academic_year`),
  ADD KEY `idx_student_enrollments_student_id` (`student_id`),
  ADD KEY `fk_student_enrollments_school` (`school_id`),
  ADD KEY `fk_student_enrollments_classroom` (`classroom_id`);

--
-- Indexes for table `student_question_responses`
--
ALTER TABLE `student_question_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sqr_student_question` (`student_id`,`question_id`),
  ADD KEY `idx_sqr_question_id` (`question_id`),
  ADD KEY `fk_sqr_import_job` (`import_job_id`);

--
-- Indexes for table `student_scores`
--
ALTER TABLE `student_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_scores_student_assessment` (`student_id`,`assessment_id`),
  ADD KEY `idx_student_scores_assessment_id` (`assessment_id`),
  ADD KEY `fk_student_scores_import_job` (`import_job_id`);

--
-- Indexes for table `student_standard_mastery`
--
ALTER TABLE `student_standard_mastery`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ssm_student_indicator_type_year` (`student_id`,`indicator_id`,`assessment_type_id`,`academic_year`),
  ADD KEY `idx_ssm_student_indicator` (`student_id`,`indicator_id`),
  ADD KEY `fk_ssm_indicator` (`indicator_id`),
  ADD KEY `fk_ssm_assessment_type` (`assessment_type_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_code`);

--
-- Indexes for table `teacher_classrooms`
--
ALTER TABLE `teacher_classrooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_teacher_classrooms_staff_classroom` (`staff_user_id`,`classroom_id`),
  ADD KEY `fk_teacher_classrooms_classroom` (`classroom_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_types`
--
ALTER TABLE `assessment_types`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classrooms`
--
ALTER TABLE `classrooms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_jobs`
--
ALTER TABLE `import_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_logs`
--
ALTER TABLE `import_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_contents`
--
ALTER TABLE `learning_contents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_indicators`
--
ALTER TABLE `learning_indicators`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_standards`
--
ALTER TABLE `learning_standards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_strands`
--
ALTER TABLE `learning_strands`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_rate_limits`
--
ALTER TABLE `login_rate_limits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_analysis`
--
ALTER TABLE `question_analysis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_secondary_indicators`
--
ALTER TABLE `question_secondary_indicators`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_exports`
--
ALTER TABLE `report_exports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_users`
--
ALTER TABLE `staff_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `standard_performance_summary`
--
ALTER TABLE `standard_performance_summary`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_question_responses`
--
ALTER TABLE `student_question_responses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_scores`
--
ALTER TABLE `student_scores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_standard_mastery`
--
ALTER TABLE `student_standard_mastery`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_classrooms`
--
ALTER TABLE `teacher_classrooms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD CONSTRAINT `fk_ai_recommendations_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`);

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `fk_assessments_assessment_type` FOREIGN KEY (`assessment_type_id`) REFERENCES `assessment_types` (`id`),
  ADD CONSTRAINT `fk_assessments_subject` FOREIGN KEY (`subject_code`) REFERENCES `subjects` (`subject_code`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_logs_actor` FOREIGN KEY (`actor_id`) REFERENCES `staff_users` (`id`);

--
-- Constraints for table `classrooms`
--
ALTER TABLE `classrooms`
  ADD CONSTRAINT `fk_classrooms_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`);

--
-- Constraints for table `import_jobs`
--
ALTER TABLE `import_jobs`
  ADD CONSTRAINT `fk_import_jobs_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`),
  ADD CONSTRAINT `fk_import_jobs_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  ADD CONSTRAINT `fk_import_jobs_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `staff_users` (`id`);

--
-- Constraints for table `import_logs`
--
ALTER TABLE `import_logs`
  ADD CONSTRAINT `fk_import_logs_actor` FOREIGN KEY (`actor_id`) REFERENCES `staff_users` (`id`),
  ADD CONSTRAINT `fk_import_logs_import_job` FOREIGN KEY (`import_job_id`) REFERENCES `import_jobs` (`id`);

--
-- Constraints for table `learning_contents`
--
ALTER TABLE `learning_contents`
  ADD CONSTRAINT `fk_learning_contents_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `learning_indicators` (`id`);

--
-- Constraints for table `learning_indicators`
--
ALTER TABLE `learning_indicators`
  ADD CONSTRAINT `fk_learning_indicators_standard` FOREIGN KEY (`standard_id`) REFERENCES `learning_standards` (`id`);

--
-- Constraints for table `learning_standards`
--
ALTER TABLE `learning_standards`
  ADD CONSTRAINT `fk_learning_standards_strand` FOREIGN KEY (`strand_id`) REFERENCES `learning_strands` (`id`);

--
-- Constraints for table `learning_strands`
--
ALTER TABLE `learning_strands`
  ADD CONSTRAINT `fk_learning_strands_subject` FOREIGN KEY (`subject_code`) REFERENCES `subjects` (`subject_code`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `fk_questions_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`),
  ADD CONSTRAINT `fk_questions_primary_indicator` FOREIGN KEY (`primary_indicator_id`) REFERENCES `learning_indicators` (`id`);

--
-- Constraints for table `question_analysis`
--
ALTER TABLE `question_analysis`
  ADD CONSTRAINT `fk_question_analysis_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);

--
-- Constraints for table `question_secondary_indicators`
--
ALTER TABLE `question_secondary_indicators`
  ADD CONSTRAINT `fk_qsi_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `learning_indicators` (`id`),
  ADD CONSTRAINT `fk_qsi_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);

--
-- Constraints for table `report_exports`
--
ALTER TABLE `report_exports`
  ADD CONSTRAINT `fk_report_exports_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `staff_users` (`id`),
  ADD CONSTRAINT `fk_report_exports_scheduled_report` FOREIGN KEY (`scheduled_report_id`) REFERENCES `scheduled_reports` (`id`);

--
-- Constraints for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  ADD CONSTRAINT `fk_scheduled_reports_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`);

--
-- Constraints for table `staff_users`
--
ALTER TABLE `staff_users`
  ADD CONSTRAINT `fk_staff_users_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`);

--
-- Constraints for table `standard_performance_summary`
--
ALTER TABLE `standard_performance_summary`
  ADD CONSTRAINT `fk_sps_assessment_type` FOREIGN KEY (`assessment_type_id`) REFERENCES `assessment_types` (`id`),
  ADD CONSTRAINT `fk_sps_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `learning_indicators` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`);

--
-- Constraints for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD CONSTRAINT `fk_student_enrollments_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`),
  ADD CONSTRAINT `fk_student_enrollments_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  ADD CONSTRAINT `fk_student_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `student_question_responses`
--
ALTER TABLE `student_question_responses`
  ADD CONSTRAINT `fk_sqr_import_job` FOREIGN KEY (`import_job_id`) REFERENCES `import_jobs` (`id`),
  ADD CONSTRAINT `fk_sqr_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  ADD CONSTRAINT `fk_sqr_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `student_scores`
--
ALTER TABLE `student_scores`
  ADD CONSTRAINT `fk_student_scores_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`),
  ADD CONSTRAINT `fk_student_scores_import_job` FOREIGN KEY (`import_job_id`) REFERENCES `import_jobs` (`id`),
  ADD CONSTRAINT `fk_student_scores_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `student_standard_mastery`
--
ALTER TABLE `student_standard_mastery`
  ADD CONSTRAINT `fk_ssm_assessment_type` FOREIGN KEY (`assessment_type_id`) REFERENCES `assessment_types` (`id`),
  ADD CONSTRAINT `fk_ssm_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `learning_indicators` (`id`),
  ADD CONSTRAINT `fk_ssm_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `teacher_classrooms`
--
ALTER TABLE `teacher_classrooms`
  ADD CONSTRAINT `fk_teacher_classrooms_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`),
  ADD CONSTRAINT `fk_teacher_classrooms_staff_user` FOREIGN KEY (`staff_user_id`) REFERENCES `staff_users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
