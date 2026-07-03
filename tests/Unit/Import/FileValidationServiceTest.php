<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import;

use DMF\Import\FileValidationService;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class FileValidationServiceTest extends TestCase
{
    private FileValidationService $service;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->service = new FileValidationService();
    }

    #[After]
    public function cleanUpTempFiles(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->tempFiles = [];
    }

    public function testAcceptsARealXlsxFile(): void
    {
        $path = $this->makeRealXlsx();

        $result = $this->service->validate($path, 'scores.xlsx', filesize($path) ?: 0);

        self::assertTrue($result->passes());
    }

    public function testAcceptsARealCsvFile(): void
    {
        $path = $this->makeFile('.csv', "student_id,score\nS001,87.5\n");

        $result = $this->service->validate($path, 'scores.csv', filesize($path) ?: 0);

        self::assertTrue($result->passes());
    }

    public function testRejectsAFileOverFiftyMegabytes(): void
    {
        $path = $this->makeFile('.csv', "a,b\n1,2\n");

        $result = $this->service->validate($path, 'scores.csv', 51 * 1024 * 1024);

        self::assertTrue($result->fails());
        self::assertSame('File exceeds the 50 MB size limit.', $result->firstError('file'));
    }

    public function testRejectsAnUnsupportedExtension(): void
    {
        $path = $this->makeFile('.pdf', '%PDF-1.4 fake content');

        $result = $this->service->validate($path, 'scores.pdf', filesize($path) ?: 0);

        self::assertTrue($result->fails());
        self::assertSame('Unsupported file type — only .xlsx and .csv are accepted.', $result->firstError('file'));
    }

    public function testRejectsContentThatDoesNotMatchItsExtension(): void
    {
        // A plain-text file renamed to .xlsx — extension says xlsx, content is not a real xlsx.
        $path = $this->makeFile('.xlsx', 'this is not a real spreadsheet');

        $result = $this->service->validate($path, 'scores.xlsx', filesize($path) ?: 0);

        self::assertTrue($result->fails());
        self::assertStringContainsString('does not match its .xlsx extension', $result->firstError('file') ?? '');
    }

    public function testRejectsAMissingFile(): void
    {
        $result = $this->service->validate(
            sys_get_temp_dir() . '/dlap-does-not-exist-' . bin2hex(random_bytes(4)) . '.csv',
            'scores.csv',
            100,
        );

        self::assertTrue($result->fails());
        self::assertSame('Uploaded file could not be found or read.', $result->firstError('file'));
    }

    public function testDetectFileTypeReadsTheExtensionLowercased(): void
    {
        self::assertSame('xlsx', $this->service->detectFileType('SCORES.XLSX'));
        self::assertSame('csv', $this->service->detectFileType('scores.csv'));
    }

    private function makeRealXlsx(): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'student_id');
        $spreadsheet->getActiveSheet()->setCellValue('A2', 'S001');

        $path = sys_get_temp_dir() . '/dlap-validate-' . bin2hex(random_bytes(4)) . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function makeFile(string $extension, string $contents): string
    {
        $path = sys_get_temp_dir() . '/dlap-validate-' . bin2hex(random_bytes(4)) . $extension;
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }
}
