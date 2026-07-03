<?php

declare(strict_types=1);

namespace DMF\Import\Session;

use DMF\Import\Score\ImportResult;

/**
 * A presentation-ready outcome for one import run — same underlying facts
 * as ImportResult, plus a human-readable one-line message, so a caller
 * (future Action layer) doesn't need to format ImportResult's raw counts
 * itself.
 */
final class ImportSummary
{
    public function __construct(
        public readonly int $importJobId,
        public readonly string $status,
        public readonly int $totalRows,
        public readonly int $committedRows,
        public readonly int $rejectedRows,
        public readonly string $message,
    ) {
    }

    public static function fromResult(ImportResult $result): self
    {
        return new self(
            $result->importJobId,
            $result->success ? 'committed' : 'failed',
            $result->totalRows,
            $result->committedRows,
            $result->totalRows - $result->committedRows,
            $result->success
                ? sprintf('%d row(s) imported successfully.', $result->committedRows)
                : sprintf(
                    'Import failed — %d row(s) could not be processed. See the error report for details.',
                    count($result->rowErrors),
                ),
        );
    }
}
