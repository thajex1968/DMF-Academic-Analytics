<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Session;

use DateTimeImmutable;
use DMF\Import\Session\DownloadErrorCsv;
use DMF\Import\Session\ImportErrorReport;
use DMF\Import\Session\RowError;
use PHPUnit\Framework\TestCase;

final class DownloadErrorCsvTest extends TestCase
{
    public function testRendersAHeaderRowAndOneRowPerError(): void
    {
        $report = new ImportErrorReport(1, [
            new RowError(2, 'bad score'),
            new RowError(4, 'unknown student_id "S099"'),
        ], new DateTimeImmutable('2026-07-03T10:00:00+07:00'));

        $csv = (new DownloadErrorCsv())->toCsv($report);
        $lines = explode("\n", trim($csv));

        self::assertSame('row,message', $lines[0]);
        self::assertSame('2,"bad score"', $lines[1]);
        self::assertSame('4,"unknown student_id ""S099"""', $lines[2]);
    }

    public function testARowZeroWholeFileErrorIsStillRendered(): void
    {
        $report = new ImportErrorReport(1, [
            new RowError(0, 'Assessment 3 not found.'),
        ], new DateTimeImmutable());

        $csv = (new DownloadErrorCsv())->toCsv($report);

        self::assertStringContainsString('0,"Assessment 3 not found."', $csv);
    }
}
