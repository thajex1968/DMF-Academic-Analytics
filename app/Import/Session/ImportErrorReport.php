<?php

declare(strict_types=1);

namespace DMF\Import\Session;

use DateTimeImmutable;

/**
 * The full, structured, user-facing error report for one failed import job
 * — what DownloadErrorCsv turns into a file.
 */
final class ImportErrorReport
{
    /** @param RowError[] $rowErrors */
    public function __construct(
        public readonly int $importJobId,
        public readonly array $rowErrors,
        public readonly DateTimeImmutable $generatedAt,
    ) {
    }
}
