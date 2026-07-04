<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

/** Dashboard-ready, whole-assessment figures. No HTML, no chart shaping. */
final class DashboardAssessment
{
    public function __construct(
        public readonly int $assessmentId,
        public readonly int $studentCount,
        public readonly int $responseCount,
        public readonly int $correctCount,
        public readonly ?float $percentCorrect,
    ) {
    }
}
