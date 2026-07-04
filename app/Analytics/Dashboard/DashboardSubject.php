<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

/** Dashboard-ready per-subject figures — mirrors Result\SubjectResult. */
final class DashboardSubject
{
    /** @param array<string, int>|null $distribution */
    public function __construct(
        public readonly string $subjectCode,
        public readonly ?float $percentCorrect,
        public readonly ?float $average,
        public readonly ?float $highest,
        public readonly ?float $lowest,
        public readonly ?array $distribution,
    ) {
    }
}
