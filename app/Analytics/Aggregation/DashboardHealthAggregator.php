<?php

declare(strict_types=1);

namespace DMF\Analytics\Aggregation;

use DMF\Analytics\Dashboard\DashboardAlert;
use DMF\Analytics\Dashboard\DashboardAlertLevel;
use DMF\Analytics\Dashboard\DashboardHealth;
use DMF\Repository\AssessmentRepository;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\StudentRepository;

/**
 * Read-only operational snapshot for Sprint 4 Phase 3 Module 7 — no
 * database repair, no admin action. `analyticsStatus` reports `'ok'`
 * whenever this class itself runs to completion: the Analytics Engine
 * performs no I/O and persists nothing of its own, so there is no other
 * operational state for it to expose.
 */
final class DashboardHealthAggregator
{
    public function __construct(
        private readonly ImportJobRepository $importJobs,
        private readonly AssessmentRepository $assessments,
        private readonly StudentRepository $students,
    ) {
    }

    public function build(): DashboardHealth
    {
        $totalStudents = $this->students->count();
        $totalAssessments = $this->assessments->count();
        $latestAssessment = $this->assessments->findLatest();
        $failedJobs = $this->importJobs->findWhere('status', 'failed');

        $warnings = [];

        if ($failedJobs !== []) {
            $warnings[] = new DashboardAlert(
                DashboardAlertLevel::WARNING,
                'import:failed',
                sprintf('%d import job(s) are in a failed state.', count($failedJobs)),
            );
        }

        if ($totalAssessments === 0) {
            $warnings[] = new DashboardAlert(
                DashboardAlertLevel::INFO,
                'assessments:none',
                'No assessments exist yet.',
            );
        }

        return new DashboardHealth(
            $failedJobs === [] ? 'ok' : 'degraded',
            'ok',
            $latestAssessment !== null ? (int) $latestAssessment['id'] : null,
            $latestAssessment !== null ? (string) $latestAssessment['subject_code'] : null,
            $latestAssessment !== null ? (int) $latestAssessment['academic_year'] : null,
            null,
            $totalStudents,
            $totalAssessments,
            $warnings,
        );
    }
}
