<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Session;

use DMF\Import\Score\ImportResult;
use DMF\Import\Session\ImportSummary;
use PHPUnit\Framework\TestCase;

final class ImportSummaryTest extends TestCase
{
    public function testASuccessfulResultProducesACommittedSummary(): void
    {
        $summary = ImportSummary::fromResult(ImportResult::success(1, 3));

        self::assertSame(1, $summary->importJobId);
        self::assertSame('committed', $summary->status);
        self::assertSame(3, $summary->totalRows);
        self::assertSame(3, $summary->committedRows);
        self::assertSame(0, $summary->rejectedRows);
        self::assertStringContainsString('3 row(s) imported successfully', $summary->message);
    }

    public function testAFailedResultProducesAFailedSummaryWithZeroCommittedRows(): void
    {
        $summary = ImportSummary::fromResult(ImportResult::failure(1, 3, ['Row 2: bad score']));

        self::assertSame('failed', $summary->status);
        self::assertSame(3, $summary->totalRows);
        self::assertSame(0, $summary->committedRows);
        self::assertSame(3, $summary->rejectedRows);
        self::assertStringContainsString('Import failed', $summary->message);
        self::assertStringContainsString('1 row(s) could not be processed', $summary->message);
    }
}
