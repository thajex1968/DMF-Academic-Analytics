<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

/** Dashboard-ready per-strand figures — mirrors Result\StrandResult. */
final class DashboardStrand
{
    public function __construct(
        public readonly int $strandId,
        public readonly string $strandCode,
        public readonly string $subjectCode,
        public readonly ?float $percentCorrect,
        public readonly int $studentCount,
        public readonly int $responseCount,
        public readonly int $correctCount,
    ) {
    }
}
