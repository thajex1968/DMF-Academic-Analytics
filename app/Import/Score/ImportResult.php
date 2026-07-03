<?php

declare(strict_types=1);

namespace DMF\Import\Score;

/**
 * The outcome of one ScoreImportService::import() run. FR-006's "no partial
 * commits" rule means this is always all-or-nothing: either every row
 * committed ($success === true, $rejectedRows === 0), or none did.
 */
final class ImportResult
{
    /**
     * @param string[] $rowErrors Human-readable "row N: message" strings, present only on failure.
     */
    public function __construct(
        public readonly int $importJobId,
        public readonly bool $success,
        public readonly int $totalRows,
        public readonly int $committedRows,
        public readonly array $rowErrors,
    ) {
    }

    public static function success(int $importJobId, int $totalRows): self
    {
        return new self($importJobId, true, $totalRows, $totalRows, []);
    }

    /** @param string[] $rowErrors */
    public static function failure(int $importJobId, int $totalRows, array $rowErrors): self
    {
        return new self($importJobId, false, $totalRows, 0, $rowErrors);
    }
}
