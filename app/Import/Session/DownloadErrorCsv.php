<?php

declare(strict_types=1);

namespace DMF\Import\Session;

use RuntimeException;

/** Renders an ImportErrorReport as a downloadable CSV (row, message columns). */
final class DownloadErrorCsv
{
    public function toCsv(ImportErrorReport $report): string
    {
        $handle = fopen('php://temp', 'r+b');

        if ($handle === false) {
            throw new RuntimeException('Unable to open a temporary stream for CSV generation.');
        }

        fputcsv($handle, ['row', 'message']);

        foreach ($report->rowErrors as $error) {
            fputcsv($handle, [$error->rowNumber, $error->message]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }
}
