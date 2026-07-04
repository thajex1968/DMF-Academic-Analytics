<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

use DateTimeImmutable;

/**
 * Read-only operational snapshot (Module 7) — no database repair, no admin
 * action, ever performed or implied by this DTO. `latestCalculation` is
 * always `null` today: the Analytics Engine persists nothing of its own
 * (Sprint 4 Phase 1's "no database writes" rule), so there is no stored
 * calculation history to report — every request recomputes live.
 */
final class DashboardHealth
{
    /** @param DashboardAlert[] $warnings */
    public function __construct(
        public readonly string $importStatus,
        public readonly string $analyticsStatus,
        public readonly ?int $latestAssessmentId,
        public readonly ?string $latestAssessmentSubjectCode,
        public readonly ?int $latestAssessmentAcademicYear,
        public readonly ?DateTimeImmutable $latestCalculation,
        public readonly int $totalStudents,
        public readonly int $totalAssessments,
        public readonly array $warnings,
    ) {
    }
}
