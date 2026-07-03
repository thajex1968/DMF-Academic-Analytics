<?php

declare(strict_types=1);

namespace DMF\Import\Session;

use DateTimeImmutable;
use DMF\Import\Score\ImportResult;
use DMF\Import\Score\ScoreImportService;
use DMF\Import\Template\ImportTemplate;

/**
 * The facade a caller (future Action layer) uses to run one import job and
 * turn its outcome into presentation-ready shapes — a thin wrapper over the
 * existing ScoreImportService (T2.3), which already owns the actual
 * parse → map → validate → resolve → normalize → commit pipeline and all
 * ImportJobManager state transitions/audit logging. This class adds no new
 * pipeline behaviour; it only reshapes ImportResult (reused as-is, never
 * replaced) for reporting.
 */
final class ImportSessionService
{
    public function __construct(
        private readonly ScoreImportService $scoreImportService,
    ) {
    }

    public function run(int $importJobId, ImportTemplate $template): ImportResult
    {
        return $this->scoreImportService->import($importJobId, $template);
    }

    public function summarize(ImportResult $result): ImportSummary
    {
        return ImportSummary::fromResult($result);
    }

    public function buildErrorReport(ImportResult $result): ImportErrorReport
    {
        $collector = new RowErrorCollector();
        $collector->collectFromImportResult($result);

        return new ImportErrorReport($result->importJobId, $collector->errors(), new DateTimeImmutable());
    }
}
