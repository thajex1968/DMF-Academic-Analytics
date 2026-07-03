<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Parser;

use DMF\Import\Parser\CsvParser;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CsvParserTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

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

    public function testParsesHeadersAndDataRows(): void
    {
        $path = $this->makeFile("student_id,full_name,score\nS001,Somchai Test,87.5\n");

        $result = (new CsvParser())->parse($path);

        self::assertSame(['student_id', 'full_name', 'score'], $result->headers);
        self::assertSame([['S001', 'Somchai Test', '87.5']], $result->rows);
    }

    public function testHandlesAQuotedFieldContainingAnEmbeddedDelimiter(): void
    {
        $path = $this->makeFile("student_id,full_name\nS001,\"Somchai, Jr.\"\n");

        $result = (new CsvParser())->parse($path);

        self::assertSame([['S001', 'Somchai, Jr.']], $result->rows);
    }

    public function testSkipsCompletelyBlankLines(): void
    {
        $path = $this->makeFile("student_id,score\nS001,87.5\n\nS002,92\n");

        $result = (new CsvParser())->parse($path);

        self::assertSame([['S001', '87.5'], ['S002', '92']], $result->rows);
    }

    public function testSupportsAConfigurableDelimiter(): void
    {
        $path = $this->makeFile("student_id;score\nS001;87.5\n");

        $result = (new CsvParser(';'))->parse($path);

        self::assertSame(['student_id', 'score'], $result->headers);
        self::assertSame([['S001', '87.5']], $result->rows);
    }

    public function testDetectsAndConvertsCp874EncodedThaiContentToUtf8(): void
    {
        $utf8Content = "student_id,full_name\nS001,ภาษาไทย\n";
        $cp874Content = iconv('UTF-8', 'CP874', $utf8Content);
        self::assertIsString($cp874Content);

        $path = $this->makeFile($cp874Content);

        $result = (new CsvParser())->parse($path);

        self::assertSame(['student_id', 'full_name'], $result->headers);
        self::assertSame([['S001', 'ภาษาไทย']], $result->rows);
    }

    public function testLeavesAlreadyValidUtf8ContentUnchanged(): void
    {
        $path = $this->makeFile("student_id,full_name\nS001,ภาษาไทย\n");

        $result = (new CsvParser())->parse($path);

        self::assertSame([['S001', 'ภาษาไทย']], $result->rows);
    }

    public function testThrowsForAMissingFile(): void
    {
        $this->expectException(RuntimeException::class);

        (new CsvParser())->parse(sys_get_temp_dir() . '/dlap-does-not-exist-' . bin2hex(random_bytes(4)) . '.csv');
    }

    private function makeFile(string $contents): string
    {
        $path = sys_get_temp_dir() . '/dlap-csv-' . bin2hex(random_bytes(4)) . '.csv';
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }
}
