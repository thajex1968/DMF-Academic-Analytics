<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Parser;

use DMF\Import\Parser\ExcelParser;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

final class ExcelParserTest extends TestCase
{
    private ExcelParser $parser;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->parser = new ExcelParser();
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

    public function testParsesHeadersAndDataRowsFromARealXlsxFile(): void
    {
        $path = $this->makeXlsx([
            ['student_id', 'full_name', 'score'],
            ['S001', 'Somchai Test', 87.5],
            ['S002', 'ภาษาไทย Student', 92],
        ]);

        $result = $this->parser->parse($path);

        self::assertSame(['student_id', 'full_name', 'score'], $result->headers);
        self::assertSame(
            [
                ['S001', 'Somchai Test', '87.5'],
                ['S002', 'ภาษาไทย Student', '92'],
            ],
            $result->rows,
        );
    }

    public function testReadsOnlyTheActiveFirstWorksheet(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Sheet1');
        $spreadsheet->getActiveSheet()->fromArray([['student_id'], ['S001']]);

        $secondSheet = $spreadsheet->createSheet();
        $secondSheet->setTitle('Sheet2');
        $secondSheet->fromArray([['should_not_be_read'], ['ignored']]);

        $path = sys_get_temp_dir() . '/dlap-excel-' . bin2hex(random_bytes(4)) . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $this->tempFiles[] = $path;

        $result = $this->parser->parse($path);

        self::assertSame(['student_id'], $result->headers);
    }

    public function testReturnsEmptyHeadersAndRowsForAnEmptySheet(): void
    {
        $spreadsheet = new Spreadsheet();
        $path = sys_get_temp_dir() . '/dlap-excel-' . bin2hex(random_bytes(4)) . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $this->tempFiles[] = $path;

        $result = $this->parser->parse($path);

        self::assertSame([], $result->headers);
        self::assertSame([], $result->rows);
    }

    public function testThrowsForAMissingFile(): void
    {
        $this->expectException(RuntimeException::class);

        $this->parser->parse(sys_get_temp_dir() . '/dlap-does-not-exist-' . bin2hex(random_bytes(4)) . '.xlsx');
    }

    public function testThrowsForAFileThatIsNotARealSpreadsheet(): void
    {
        $path = sys_get_temp_dir() . '/dlap-excel-fake-' . bin2hex(random_bytes(4)) . '.xlsx';
        file_put_contents($path, 'this is not a real spreadsheet');
        $this->tempFiles[] = $path;

        $this->expectException(RuntimeException::class);

        $this->parser->parse($path);
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function makeXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);

        $path = sys_get_temp_dir() . '/dlap-excel-' . bin2hex(random_bytes(4)) . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $this->tempFiles[] = $path;

        return $path;
    }
}
