<?php

declare(strict_types=1);

namespace DMF\Import\Session;

use DateTimeImmutable;
use DMF\Import\ImportJobManager;
use DMF\Repository\ImportJobRepository;

/**
 * Read side of the Import Session & Error Reporting feature: past jobs for a
 * school, one job's full audit trail (reuses ImportJobManager::history(),
 * FR-008), and — for a `failed` job — a structured ImportErrorReport
 * reconstructed from the `import_jobs.error_detail` text ImportJobManager
 * ::markFailed() already persisted, so a report is downloadable long after
 * the run() call that produced it has returned.
 */
final class ImportHistory
{
    public function __construct(
        private readonly ImportJobRepository $jobs,
        private readonly ImportJobManager $jobManager,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function forSchool(int $schoolId): array
    {
        return $this->jobs->findBySchool($schoolId);
    }

    /** @return array<int, array<string, mixed>> */
    public function timeline(int $importJobId): array
    {
        return $this->jobManager->history($importJobId);
    }

    public function errorReportFor(int $importJobId): ?ImportErrorReport
    {
        $job = $this->jobs->findById($importJobId);

        if ($job === null || $job['status'] !== 'failed') {
            return null;
        }

        $errorDetail = $job['error_detail'] ?? null;

        if ($errorDetail === null || $errorDetail === '') {
            return null;
        }

        $collector = new RowErrorCollector();
        $collector->collectFromRawErrorDetail((string) $errorDetail);

        return new ImportErrorReport($importJobId, $collector->errors(), new DateTimeImmutable());
    }
}
