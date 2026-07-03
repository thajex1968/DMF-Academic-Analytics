<?php

declare(strict_types=1);

/**
 * Cron entry point for the Score Import Pipeline (T2.7, decisions/IDR-009).
 * Invoked by crontab as `php ~/public_html/api/cron/import_runner.php`,
 * matching ../grade.dmf.ac.th's established convention for cron-invoked
 * PHP (a plain, non-shebang script under public_html/api/, not a bin/
 * directory or Console/Command framework) — there is no other precedent
 * anywhere in the DMF Platform family.
 *
 * Deliberately refuses to run under any SAPI other than `cli`: this file
 * lives under public_html/ (web-reachable unless blocked at the web-server
 * level) but must only ever be triggered by cron, never by an anonymous
 * HTTP request — this guard is the safeguard against that, independent of
 * (not a replacement for) any .htaccess-level restriction deployed
 * alongside it.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Forbidden.\n";

    exit(1);
}

use DMF\Database\ConnectionFactory;
use DMF\Import\Cron\ImportJobRunner;
use DMF\Import\ImportJobManager;
use DMF\Import\Parser\CsvParser;
use DMF\Import\Parser\ExcelParser;
use DMF\Import\Score\AssessmentResolver;
use DMF\Import\Score\ImportTransactionService;
use DMF\Import\Score\RowValidator;
use DMF\Import\Score\ScoreImportService;
use DMF\Import\Score\ScoreNormalizer;
use DMF\Import\Score\StudentResolver;
use DMF\Import\Session\ImportSessionService;
use DMF\Import\Audit\AuditTrailService;
use DMF\Import\Audit\DuplicateDetectionService;
use DMF\Import\Audit\ImportAuditLogger;
use DMF\Import\Template\ColumnMapper;
use DMF\Import\Template\ExampleTemplates;
use DMF\Import\Template\TemplateRegistry;
use DMF\Import\Template\TemplateResolver;
use DMF\Import\Template\TemplateValidator;
use DMF\Repository\AssessmentRepository;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\ImportLogRepository;
use DMF\Repository\StudentRepository;
use DMF\Repository\StudentScoreRepository;

$config = require dirname(__DIR__, 3) . '/bootstrap/app.php';

$connection = ConnectionFactory::fromConfig($config);

$jobManager = new ImportJobManager(new ImportJobRepository($connection), new ImportLogRepository($connection));

$scoreImportService = new ScoreImportService(
    new ImportJobRepository($connection),
    $jobManager,
    ['xlsx' => new ExcelParser(), 'csv' => new CsvParser()],
    new ColumnMapper(),
    new RowValidator(new TemplateValidator()),
    new StudentResolver(new StudentRepository($connection)),
    new AssessmentResolver(new AssessmentRepository($connection)),
    new ScoreNormalizer(),
    new ImportTransactionService($connection, new StudentScoreRepository($connection)),
    new DuplicateDetectionService(new StudentScoreRepository($connection), new ImportJobRepository($connection)),
    new AuditTrailService(
        new ImportAuditLogger(new ImportLogRepository($connection)),
        new ImportLogRepository($connection),
        new ImportJobRepository($connection),
    ),
);

// v1.0: the one example template stands in for a real, per-academic-year สทศ
// template registry that does not exist yet — decisions/IDR-009.
$defaultTemplate = ExampleTemplates::studentIdAndScore();
$templateRegistry = new TemplateRegistry();
$templateRegistry->register($defaultTemplate);

$runner = new ImportJobRunner(
    $jobManager,
    new ImportSessionService($scoreImportService),
    new TemplateResolver($templateRegistry, $defaultTemplate->key),
);

$summary = $runner->run();

printf(
    "[%s] import_runner: processed=%d success=%d failed=%d\n",
    date('c'),
    $summary->processedCount(),
    $summary->successCount(),
    $summary->failureCount(),
);

exit($summary->failureCount() > 0 ? 1 : 0);
