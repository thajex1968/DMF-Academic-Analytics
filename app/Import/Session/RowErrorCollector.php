<?php

declare(strict_types=1);

namespace DMF\Import\Session;

use DMF\Import\Score\ImportResult;

/**
 * Turns ScoreImportService's flat `"Row N: message"` strings (ImportResult
 * ::$rowErrors, and the same text persisted verbatim to
 * `import_jobs.error_detail` by ImportJobManager::markFailed(), pipe-joined)
 * into structured, traceable RowError objects.
 *
 * Also the one place a caller-facing error report gets sanitized: a
 * `ScoreImportService::fail()` triggered by a commit-time Throwable embeds
 * that exception's raw message (which can be a raw database/driver error)
 * behind a "Commit failed:" prefix. That raw text stays in
 * `import_jobs.error_detail` for diagnostics but is never surfaced verbatim
 * here — this is what "user-friendly error reports without exposing
 * internal exceptions" (T2.4) means in practice.
 */
final class RowErrorCollector
{
    private const INTERNAL_FAILURE_PREFIX = 'Commit failed:';

    private const INTERNAL_FAILURE_MESSAGE =
        'An internal error occurred while saving this import. Please try again or contact support.';

    /** @var RowError[] */
    private array $errors = [];

    public function collectFromImportResult(ImportResult $result): void
    {
        foreach ($result->rowErrors as $rawError) {
            $this->errors[] = $this->parse($rawError);
        }
    }

    /** Rebuilds a report for a past job from `import_jobs.error_detail` (pipe-joined row errors). */
    public function collectFromRawErrorDetail(string $errorDetail): void
    {
        foreach (explode(' | ', $errorDetail) as $rawError) {
            $this->errors[] = $this->parse($rawError);
        }
    }

    /** @return RowError[] */
    public function errors(): array
    {
        return $this->errors;
    }

    private function parse(string $rawError): RowError
    {
        if (preg_match('/^Row (\d+): (.+)$/', $rawError, $matches) === 1) {
            return new RowError((int) $matches[1], $matches[2]);
        }

        if (str_starts_with($rawError, self::INTERNAL_FAILURE_PREFIX)) {
            return new RowError(0, self::INTERNAL_FAILURE_MESSAGE);
        }

        return new RowError(0, $rawError);
    }
}
