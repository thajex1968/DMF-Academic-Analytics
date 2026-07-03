# Data Dictionary

**DMF Learning Analytics Platform (DLAP) — Database `dmf_academic`**

| | |
|---|---|
| **Document ID** | ONET-DOC-006 |
| **Version** | 1.0.1 |
| **Status** | Frozen — DLAP Documentation Baseline v2.0.0 |
| **Date** | 2026-07-02 |
| **Author** | DMF Platform Team |
| **Related documents** | [00-Project-Overview](00-Project-Overview.md) · [01-PRD](01-PRD.md) · [03-Database-Design](03-Database-Design.md) · [Naming-Convention](Naming-Convention.md) |

## Revision History

| Version | Date | Description | Author |
|---|---|---|---|
| 1.0.0 | 2026-07-02 | Initial release. Field-level business meaning and validation rules for every table in `dmf_academic` v2.0.0. | DMF Platform Team |
| 1.0.1 | 2026-07-02 | QA fix (see [Documentation-QA-Report.md](Documentation-QA-Report.md)): corrected the `assessment_types.code` row's "eleven reserved codes" to "eleven codes total, ten reserved." Frozen as part of the DLAP Documentation Baseline v2.0.0 ([00-Project-Overview.md §13](00-Project-Overview.md#13-documentation-freeze)). | DMF Platform Team |

## Purpose and Relationship to 03-Database-Design.md

[03-Database-Design.md](03-Database-Design.md) is the schema's structural reference — column
types, keys, defaults, indexes, ER diagram. This document is its business-meaning companion: for
each field, *what it means* to someone reading a report, and *what makes a value valid*, beyond
what a `NOT NULL` or a foreign key already enforces at the database level. Per
[Architecture-Principles.md §1](Architecture-Principles.md#1-single-source-of-truth-ssot), a
column's type/key/default is authored once, in 03-Database-Design.md, and not repeated here in
full — this document adds the layer 03-Database-Design.md deliberately keeps out of its DDL-style
tables: validation rules that are business logic, not schema constraints.

## Table of Contents

1. [Organizational](#1-organizational)
2. [Assessment Framework](#2-assessment-framework)
3. [Standards Catalogue](#3-standards-catalogue)
4. [Questions & Item Mapping](#4-questions--item-mapping)
5. [Import Pipeline](#5-import-pipeline)
6. [Scores & Responses](#6-scores--responses)
7. [Aggregation & Materialized Summaries](#7-aggregation--materialized-summaries)
8. [Reporting, Diagnostics & Platform](#8-reporting-diagnostics--platform)
9. [Cross-References](#9-cross-references)

---

## 1. Organizational

### `schools`
| Field | Description | Validation |
|---|---|---|
| id | Internal surrogate identifier for the school. | System-generated; never exposed in a URL or export — external references use `school_code`. |
| school_code | The Ministry of Education's official school code. | Exactly matches the format used on official documents (e.g., `47010005`); unique across the table (v1.0 has exactly one row). |
| name_th | The school's full Thai name. | Required; Thai script expected, not transliterated. |
| province | The province the school is located in. | Required; matches a standard Thai province name. |

### `classrooms`
| Field | Description | Validation |
|---|---|---|
| grade_level | Which primary grade (ป.1–ป.6) this classroom is. | Integer 1–6 inclusive. v1.0 only ever creates `6`, since only O-NET (Grade 6) is implemented — see [01-PRD.md §6](01-PRD.md#6-scope). |
| room_label | The human-readable room name shown throughout the UI. | Required; conventionally `ป.<grade>/<section>` (e.g., `ป.6/1`). |
| academic_year | The Buddhist Era (พ.ศ.) academic year this classroom exists for. | 4-digit integer, Buddhist Era (e.g., `2569`, not `2026`); a classroom never spans two academic years. |
| *(school_id, room_label, academic_year)* | — | The combination must be unique — the same room label cannot be registered twice for the same school in the same year. |

### `students`
| Field | Description | Validation |
|---|---|---|
| student_id | The stable identifier that follows a student through every grade at this school. | Required, unique, immutable once assigned — never reused for a different student even after graduation. |
| classroom_id | **Denormalized** pointer to the student's *current* classroom. | Must always equal the `classroom_id` of the student's most recent `student_enrollments` row; kept in sync exclusively by the Student & Enrollment module — see [02-System-Architecture.md §3](02-System-Architecture.md#3-module-decomposition). Application code must never write this column directly to record a grade change; write a new `student_enrollments` row instead. |
| full_name | The student's full name, as it appears on official records. | Required. Subject to the retention rule below once a student's `status` becomes `graduated`/`transferred` for more than one academic year — see [03-Database-Design.md §15](03-Database-Design.md#15-data-retention--privacy). |
| national_id | The student's 13-digit Thai national ID. | Optional (not every student record includes it at entry); when present, must pass `dmf-core`'s `NationalIdRule` (MOD-11 checksum) and be unique across the table. |
| status | The student's current enrollment state at this school. | One of `active`, `transferred`, `graduated`. Transitions are one-directional — a `graduated` or `transferred` student is never moved back to `active` by editing this field; a genuine re-enrollment creates a new `student_enrollments` row. |

### `student_enrollments`
| Field | Description | Validation |
|---|---|---|
| student_id, academic_year | Together, identify "which grade was this student in during this academic year." | The pair must be unique — a student cannot have two enrollment records for the same academic year (they cannot be in two grades at once). |
| grade_level | The grade the student was in during this academic year. | Integer 1–6; must equal the `grade_level` of the referenced `classroom_id` at the time the row is written — kept consistent by the Student & Enrollment module, not hand-edited. |
| enrollment_status | The outcome of this specific academic year's enrollment. | One of `active`, `transferred`, `graduated`, `repeated`. `repeated` is set when a student's `grade_level` for this row equals their prior year's `grade_level` (grade repetition), which is a legitimate, expected value — not an error condition. |

### `staff_users`
| Field | Description | Validation |
|---|---|---|
| username | The credential a teacher/director/admin logs in with. | Required, unique; not the same value space as `student_id` (staff and students are never confused by ID lookup). |
| password_hash | The bcrypt hash of the account's password. | Never populated with plaintext, even transiently in application memory beyond the hashing call — see [Architecture-Decision-Record.md, ADR-003](Architecture-Decision-Record.md#adr-003--why-mysqlmariadb) and `dmf-core`'s `Security\PasswordHasher`. |
| role | Which permission tier this account has. | One of `teacher`, `director`, `admin`, `inspector`. `inspector` is a reserved value — no v1.0 account is created with this role, since the multi-school phase it belongs to is not built yet ([01-PRD.md §7](01-PRD.md#7-out-of-scope)). |
| is_active | Whether the account can currently authenticate. | Boolean; set to `0` (not deleted) when staff leave, so historical `actor_id` references in `audit_logs`/`import_logs` remain resolvable. |

### `teacher_classrooms`
| Field | Description | Validation |
|---|---|---|
| is_current | Whether this assignment is the teacher's active one for the current academic year. | Boolean. At most one `is_current = 1` row per `(staff_user_id, classroom_id)` combination is meaningful at a time — the application, not a database constraint, is responsible for flipping the prior year's row to `0` when a new one is created, mirroring `dmf_grade.teacher_classroom_history`. |

## 2. Assessment Framework

### `assessment_types`
| Field | Description | Validation |
|---|---|---|
| code | The stable, machine-readable identifier for an assessment type. | One of the eleven codes in [01-PRD.md §6](01-PRD.md#6-scope): `PRE_TEST`, `MID_TEST`, `POST_TEST`, `ONET`, `NT`, `RT`, `LAS`, `CLASSROOM_ASSESSMENT`, `READING_ASSESSMENT`, `WRITING_ASSESSMENT`, `COMPETENCY_ASSESSMENT` — of which only `ONET` is active in v1.0 (see `is_active` below); the other ten are reserved. Never a freeform value — new codes are added through the Approval Flow ([01-PRD.md §21](01-PRD.md#21-core-product-capabilities)), not typed ad hoc by application code. |
| is_active | Whether this assessment type has an implemented import pipeline and dashboard support. | Boolean. **v1.0: only the `ONET` row has `is_active = 1`.** Application code must check this flag before offering an assessment type in any UI or accepting an import against it — an inactive type existing as a row is not the same as it being usable. |

### `assessments`
| Field | Description | Validation |
|---|---|---|
| *(assessment_type_id, subject_code, academic_year)* | Together, identify one specific administration — e.g., "O-NET, Mathematics, academic year 2569." | Must be unique; there is exactly one `assessments` row per type/subject/year combination, never more even if the assessment is administered on multiple dates within the same year. |
| grade_level | The grade level this assessment targets. | Integer 1–6. v1.0 data is `6` only (O-NET); the column accepts the full range so a future Grade 1 RT or Grade 3 NT assessment does not require a schema change. |
| name_th | The human-readable Thai label shown in dashboards and exports. | Required; should be unambiguous about type, subject, and year even out of context (e.g., "O-NET ป.6 คณิตศาสตร์ ปีการศึกษา 2569"), since it appears in exported reports without other columns alongside it. |

## 3. Standards Catalogue

### `subjects`
| Field | Description | Validation |
|---|---|---|
| subject_code | The stable short code for a subject. | e.g., `THAI`, `MATH`, `SCI`, `ENG`. Referenced by both `assessments` and `learning_strands` — changing a code after data exists requires an explicit migration, not a simple `UPDATE`, because it is a natural-key foreign key elsewhere. |
| is_active | Whether the subject is currently examined. | Boolean; set to `0` rather than deleting a row if a subject (e.g., Social Studies) is dropped from an assessment, per the historical-record note under `assessment_types.is_active` above. |

### `learning_strands` (สาระการเรียนรู้)
| Field | Description | Validation |
|---|---|---|
| strand_code | The official curriculum code for this strand. | Unique within its subject; sourced from the Basic Education Core Curriculum, not invented locally. |

### `learning_standards` (มาตรฐานการเรียนรู้)
| Field | Description | Validation |
|---|---|---|
| standard_code | The official curriculum code for this standard (e.g., `ค 1.1`). | Unique within its strand; sourced from the national curriculum document, per the Approval Flow ([01-PRD.md §21](01-PRD.md#21-core-product-capabilities)) for any correction. |

### `learning_indicators` (ตัวชี้วัด)
| Field | Description | Validation |
|---|---|---|
| indicator_code | The official curriculum code for this indicator (e.g., `ค 1.1 ป.6/1`) — the finest-grained unit any assessment item is mapped to. | Unique within its standard and curriculum revision. This is the field `question.primary_indicator_id`, `question_secondary_indicators`, `standard_performance_summary`, and `student_standard_mastery` all ultimately resolve to — every performance figure in the platform traces back to one row here. |
| grade_level | Which curriculum grade level this indicator targets. | Integer 1–6. v1.0 data is `6` only, matching the O-NET-only implementation, though the curriculum itself defines indicators at every grade. |
| curriculum_revision | The Buddhist Era year of the curriculum revision this indicator belongs to. | e.g., `2560`. Exists so a future curriculum revision creates *new* indicator rows rather than silently changing the meaning of an existing `indicator_code` a historical `student_standard_mastery` row already references. |

## 4. Questions & Item Mapping

### `questions`
| Field | Description | Validation |
|---|---|---|
| assessment_id, item_number | Together, identify one specific question — e.g., "item 12 of the O-NET Mathematics ป.6 2569 assessment." | The pair must be unique; `item_number` alone is only unique *within* one assessment, since every assessment restarts its own item numbering. |
| primary_indicator_id | The single learning indicator this question is primarily assessing. | Required (`NOT NULL`) — a question can never exist in a committed state without a primary indicator; this is what makes PRD FR-009's "100% of items mapped" rule a structural guarantee, not a hopeful convention. |
| correct_choice | Which answer choice (`1`–`4`) is correct. | Optional — some official answer-key exports omit this for a subset of items (e.g., pending review); `NULL` here does not block a question from being imported, but does block any `student_question_responses.is_correct` computation for that item until it is filled in. |

### `question_secondary_indicators`
| Field | Description | Validation |
|---|---|---|
| question_id, indicator_id | A question can be *additionally* linked to indicators beyond its one primary indicator. | The pair must be unique (no duplicate secondary link); `indicator_id` here must differ from that same question's `primary_indicator_id` — a primary link is not also recorded as a secondary one. |

## 5. Import Pipeline

### `import_jobs`
| Field | Description | Validation |
|---|---|---|
| assessment_id | Which assessment administration this file's data belongs to. | Required; the file's content is validated (structurally and by content) against the subject/grade/year implied by this assessment, so an O-NET Mathematics file cannot be accidentally committed against the Science assessment row. |
| file_type | Which parser handles this file. | One of `pdf`, `xlsx`, `csv` — determined from the actual uploaded file's content/MIME type, not merely its filename extension (extension can be spoofed or wrong). |
| status | Where this import is in the pipeline. | One of `queued`, `processing`, `committed`, `failed`. Transitions only move forward (`queued → processing → committed` or `queued → processing → failed`) — never backward, and `committed`/`failed` are terminal; a correction is a new `import_jobs` row, not a status reset on an old one. |
| error_detail | The specific reason a `failed` import was rejected. | Populated only when `status = 'failed'`; must be specific enough to name a row/column/value, per PRD FR-006's acceptance criteria — a generic "import failed" message is not an acceptable value. |

### `import_logs`
| Field | Description | Validation |
|---|---|---|
| event | Which pipeline stage produced this log entry. | One of `queued`, `parsed`, `validated`, `mapped`, `committed`, `rejected` — append-only; existing rows are never updated or deleted, since this table is the audit trail PRD FR-008 requires. |
| actor_id | Who triggered this event. | `NULL` for system/cron-generated events (e.g., the cron picking up a queued job); populated with a `staff_users.id` only for a human-triggered event (e.g., the original upload). |

## 6. Scores & Responses

### `student_scores`
| Field | Description | Validation |
|---|---|---|
| student_id, assessment_id | Together, identify one student's result on one assessment administration. | The pair must be unique — a student has at most one committed score per assessment; a corrected re-import supersedes the row via a new `import_job_id`, never a second score row for the same pair. |
| score | The student's raw score on this assessment. | `0.00`–`100.00` inclusive, enforced by a `CHECK` constraint where the MySQL/MariaDB version supports it, and redundantly at the application layer everywhere else (see [Architecture-Decision-Record.md, ADR-003](Architecture-Decision-Record.md#adr-003--why-mysqlmariadb)). |

### `student_question_responses`
| Field | Description | Validation |
|---|---|---|
| student_id, question_id | Together, identify one student's answer to one specific question. | The pair must be unique. |
| selected_choice | Which answer choice (`1`–`4`) the student selected. | Optional — `NULL` when the source file only contained subject-level totals, not item-level responses (a valid, expected case, not an error — see [03-Database-Design.md §13](03-Database-Design.md#13-data-integrity-rules)). |
| is_correct | Whether `selected_choice` matches `questions.correct_choice`. | Denormalized (computed at import time, not read live) purely for aggregation speed ([02-System-Architecture.md §8](02-System-Architecture.md#8-analytics--aggregation-architecture)); if `questions.correct_choice` is later corrected, every affected `is_correct` value must be recomputed as part of that correction, not left stale. |

## 7. Aggregation & Materialized Summaries

All fields in this section are **derived** — nothing here is entered by a person; every value is
computed by the Analytics module from `student_scores`/`student_question_responses` and rewritten
on the schedule described in [03-Database-Design.md §14](03-Database-Design.md#14-aggregation-recompute-strategy).
Treat a "wrong" value here as a recompute bug, never hand-correct a row directly.

### `standard_performance_summary`
| Field | Description | Validation |
|---|---|---|
| scope, scope_id | Which aggregation tier this row summarizes — a specific classroom, or a whole grade/school. | `scope` is one of `classroom`, `grade`, `school`; `scope_id` is a `classrooms.id` when `scope = 'classroom'`, or a `schools.id` for the other two tiers — application code must branch on `scope` to know how to interpret `scope_id`, since it is not a single foreign key. |
| percent_correct | What fraction of relevant responses were correct, for this indicator/scope/year. | `0.00`–`100.00`; only meaningful together with `student_count` — a `percent_correct` of `100.00` from one student carries different weight than from thirty, which is why `student_count` is stored alongside it rather than discarded after computation. |

### `student_standard_mastery`
| Field | Description | Validation |
|---|---|---|
| student_id, indicator_id, assessment_type_id, academic_year | Together, identify one student's measured performance on one indicator, via one assessment type, in one year. | The tuple must be unique. **This table is not populated in v1.0** — see the status note in [03-Database-Design.md §9](03-Database-Design.md#9-table-definitions--aggregation--materialized-summaries); any row present before that phase ships would indicate a process running ahead of its documented scope. |
| grade_level | The grade the student was in when this measurement was taken. | Denormalized from `student_enrollments` at computation time — this is what lets a longitudinal query plot "percent_correct over grade_level" without re-joining enrollment history per row. |

### `question_analysis`
| Field | Description | Validation |
|---|---|---|
| difficulty_index | The CTT p-value — the proportion of students who answered this question correctly. | `0.000`–`1.000`; a value near `0` or `1` indicates an item too hard or too easy to discriminate between students, which is a meaningful analytical signal, not a data-quality problem to "fix." |
| discrimination_index | The point-biserial correlation between getting this item right and the student's overall score. | Typically `-1.000`–`1.000`; a negative value is a legitimate (if concerning) analytical finding — a well-performing student answering a specific item incorrectly more often than weaker students — and should never be clamped or discarded. |
| distractor_frequency_json | How often each answer choice was selected. | A JSON object keyed `"1"`–`"4"` with values summing to (approximately) `1.0`; computed only from responses where `selected_choice IS NOT NULL`. |

## 8. Reporting, Diagnostics & Platform

### `learning_contents`
| Field | Description | Validation |
|---|---|---|
| resource_type | What kind of material this is. | One of `worksheet`, `video`, `lesson_plan`, `external_link`. |
| url_or_path | Where to find the resource. | For `external_link`, a full URL; for the others, a path under the module's managed storage — never a raw filesystem path outside `storage/` ([02-System-Architecture.md §5](02-System-Architecture.md#5-repository--directory-structure)). |

### `ai_recommendations`
| Field | Description | Validation |
|---|---|---|
| source | Whether this recommendation came from the deterministic rule engine or an LLM call. | One of `rule_based`, `llm`. A `narrative` value is only ever present when `source = 'llm'` — a `rule_based` recommendation is a list (stored/derived elsewhere), not prose. |
| narrative | The free-text summary generated for this classroom/year. | Must never contain a student's name, national ID, or any other directly identifying field — the Diagnostics module only has read access to aggregate summary tables, never to `students`, which is what enforces this structurally ([02-System-Architecture.md §11](02-System-Architecture.md#11-ai-diagnostics-integration)). |

### `scheduled_reports`
| Field | Description | Validation |
|---|---|---|
| recipient_emails | Who receives this report on schedule. | Comma-separated list; each address validated at write time via `dmf-core`'s `Security\Sanitizer` — never accepted unvalidated from a form submission. |
| cron_expression | When this report is generated and sent. | Standard 5-field cron syntax (e.g., `0 7 1 * *`); validated for syntactic correctness before the row is saved, so a malformed expression cannot silently stop the scheduler. |

### `report_exports`
| Field | Description | Validation |
|---|---|---|
| scheduled_report_id | Which schedule produced this export, if any. | `NULL` for an on-demand (director-initiated) export — this column is how the same table serves both scheduled and ad hoc generation without two separate tables. |
| status | Whether this specific export attempt succeeded. | One of `generated`, `sent`, `failed`; `sent` only applies to scheduled exports (an on-demand export is downloaded, not emailed, so it terminates at `generated`). |

### `audit_logs`
| Field | Description | Validation |
|---|---|---|
| action | What was done, in `module.verb` form. | e.g., `standards_catalogue.update` — a fixed, reviewable vocabulary, not a freeform message (freeform detail belongs in `detail_json`, not `action`). |
| detail_json | The specific before/after or parameters of the action. | Structured JSON; must never include a password, token, or other secret value, even in an "old value" field during a credential change. |

### `login_rate_limits`
| Field | Description | Validation |
|---|---|---|
| failed_attempts | Consecutive failed login attempts for this username since the last successful login or lockout expiry. | Resets to `0` on a successful login; increments on each failure. At `5`, `locked_until` is set per PRD FR-001's business rule. |
| locked_until | The timestamp after which login attempts are accepted again. | `NULL` when not locked; once set, login attempts are rejected with a `429` (not a `401`, so the client can distinguish "wrong password" from "temporarily locked") until this timestamp passes. |

## 9. Cross-References

* Structural definition (types, keys, indexes, ER diagram) for every table above: [03-Database-Design.md](03-Database-Design.md).
* Naming rules these field names follow: [Naming-Convention.md](Naming-Convention.md).
* The functional requirements these validation rules exist to satisfy: [01-PRD.md](01-PRD.md), FR-006 (validation), FR-007 (duplicate detection), FR-009 (standard mapping).
