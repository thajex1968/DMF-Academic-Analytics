<?php

declare(strict_types=1);

namespace DMF\Tests\Fixtures\Import;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/**
 * Produces the Golden Test Dataset in this directory — see README.md for
 * what each fixture is a regression test for. Every fixture uses the
 * test-only DMF\Import\Template\ExampleTemplates::studentIdAndScore()
 * column set (รหัสนักเรียน/เลขประจำตัว → student_id, score → score).
 *
 * Not run automatically by the test suite — regenerate.php in this
 * directory invokes this class manually when a fixture needs to change.
 */
final class GoldenDatasetGenerator
{
    public function __construct(private readonly string $directory)
    {
    }

    public function generate(): void
    {
        $valid = [
            ['รหัสนักเรียน', 'score'],
            ['S001', '87.5'],
            ['S002', '92'],
            ['S003', '65.25'],
        ];

        $this->writeXlsx('valid_onet.xlsx', $valid);
        $this->writeCsv('valid_onet.csv', $valid);

        $this->writeXlsx('missing_student_id.xlsx', [
            ['รหัสนักเรียน', 'score'],
            ['S001', '87.5'],
            ['', '92'],
            ['S003', '65'],
        ]);

        $this->writeXlsx('duplicate_student.xlsx', [
            ['รหัสนักเรียน', 'score'],
            ['S001', '87.5'],
            ['S001', '92'],
            ['S003', '65'],
        ]);

        $this->writeXlsx('invalid_score.xlsx', [
            ['รหัสนักเรียน', 'score'],
            ['S001', '87.5'],
            ['S002', '150'],
            ['S003', 'abc'],
        ]);

        $this->writeXlsx('missing_required_column.xlsx', [
            ['รหัสนักเรียน'],
            ['S001'],
            ['S002'],
        ]);

        $this->writeBlankRowsFixture();

        $this->writeCsv('utf8.csv', $valid, 'UTF-8');
        $this->writeCsv('tis620.csv', $valid, 'CP874');

        echo "done\n";
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function writeXlsx(string $filename, array $rows): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);

        $path = $this->directory . '/' . $filename;
        (new Xlsx($spreadsheet))->save($path);
        echo "wrote {$path}\n";
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function writeCsv(string $filename, array $rows, string $encoding = 'UTF-8'): void
    {
        $lines = array_map(
            static fn (array $row): string => implode(',', array_map(
                static fn (mixed $cell): string => (string) $cell,
                $row,
            )),
            $rows,
        );
        $content = implode("\n", $lines) . "\n";

        if ($encoding !== 'UTF-8') {
            $converted = iconv('UTF-8', $encoding, $content);

            if ($converted === false) {
                throw new RuntimeException("iconv UTF-8 -> {$encoding} failed for {$filename}");
            }

            $content = $converted;
        }

        $path = $this->directory . '/' . $filename;
        file_put_contents($path, $content);
        echo "wrote {$path}\n";
    }

    /** A genuinely blank row (row 3) between two data rows. */
    private function writeBlankRowsFixture(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['รหัสนักเรียน', 'score'],
            ['S001', '87.5'],
        ]);
        // Row 3 (index 2) intentionally left blank; row 4 continues with data.
        $spreadsheet->getActiveSheet()->fromArray([['S002', '92']], null, 'A4');

        $path = $this->directory . '/blank_rows.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        echo "wrote {$path}\n";
    }
}
