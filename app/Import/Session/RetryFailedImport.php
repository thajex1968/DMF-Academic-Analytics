<?php

declare(strict_types=1);

namespace DMF\Import\Session;

use DMF\Import\Audit\AuditTrailService;
use DMF\Import\ImportJobManager;
use DMF\Import\Score\ImportResult;
use DMF\Import\Template\ImportTemplate;
use DMF\Repository\ImportJobRepository;
use InvalidArgumentException;
use RuntimeException;

/**
 * Re-queues a `failed` import job and re-runs it through ImportSessionService
 * (which in turn reuses ScoreImportService/ImportJobManager unchanged — no
 * new pipeline logic here). Only a `failed` job may be retried; `queued`/
 * `processing`/`committed` jobs are left alone.
 *
 * Continues to log the retry via the existing `queued` event + a
 * distinguishing message (unchanged since T2.4), **and** additionally logs
 * the T2.6 `retry` event via AuditTrailService — the former keeps this
 * class's already-approved behavior intact, the latter makes "Retry" a
 * first-class, queryable audit event per decisions/IDR-008.
 */
final class RetryFailedImport
{
    public function __construct(
        private readonly ImportJobRepository $jobs,
        private readonly ImportJobManager $jobManager,
        private readonly ImportSessionService $sessionService,
        private readonly AuditTrailService $auditTrail,
    ) {
    }

    /** @throws RuntimeException If the job does not exist. */
    public function execute(int $importJobId, ImportTemplate $template): ImportResult
    {
        $job = $this->jobs->findById($importJobId);

        if ($job === null) {
            throw new RuntimeException(sprintf('Import job %d not found.', $importJobId));
        }

        if ($job['status'] !== 'failed') {
            throw new InvalidArgumentException(
                sprintf(
                    'Import job %d cannot be retried (status: %s, expected "failed").',
                    $importJobId,
                    (string) $job['status'],
                ),
            );
        }

        $this->jobs->update($importJobId, ['status' => 'queued', 'error_detail' => null]);
        $this->jobManager->log($importJobId, 'queued', 'Retry requested.');
        $this->auditTrail->recordRetry($importJobId, (int) $job['school_id']);

        return $this->sessionService->run($importJobId, $template);
    }
}
