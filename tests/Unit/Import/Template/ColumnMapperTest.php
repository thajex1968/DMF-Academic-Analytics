<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Template;

use DMF\Import\ParsedFile;
use DMF\Import\Template\ColumnMapper;
use DMF\Import\Template\ColumnMapping;
use PHPUnit\Framework\TestCase;

final class ColumnMapperTest extends TestCase
{
    public function testMapsPositionalRowsToCanonicalFieldNames(): void
    {
        $file = new ParsedFile(
            ['รหัสนักเรียน', 'คะแนน'],
            [['S001', '87.5'], ['S002', '92']],
        );
        $mapping = new ColumnMapping([
            'student_id' => ['รหัสนักเรียน'],
            'score' => ['คะแนน'],
        ]);

        $result = (new ColumnMapper())->map($file, $mapping);

        self::assertSame(
            [
                ['student_id' => 'S001', 'score' => '87.5'],
                ['student_id' => 'S002', 'score' => '92'],
            ],
            $result->mappedRows,
        );
        self::assertSame([], $result->unmappedHeaders);
    }

    public function testSurfacesUnmappedHeadersWithoutFailing(): void
    {
        $file = new ParsedFile(
            ['รหัสนักเรียน', 'some unrelated column'],
            [['S001', 'x']],
        );
        $mapping = new ColumnMapping(['student_id' => ['รหัสนักเรียน']]);

        $result = (new ColumnMapper())->map($file, $mapping);

        self::assertSame(['student_id' => 'S001'], $result->mappedRows[0]);
        self::assertSame(['some unrelated column'], $result->unmappedHeaders);
    }

    public function testResolvesEitherFr004AliasToTheSameCanonicalField(): void
    {
        $mapping = new ColumnMapping(['student_id' => ['รหัสนักเรียน', 'เลขประจำตัว']]);

        $fileA = new ParsedFile(['รหัสนักเรียน'], [['S001']]);
        $fileB = new ParsedFile(['เลขประจำตัว'], [['S002']]);

        self::assertSame(['student_id' => 'S001'], (new ColumnMapper())->map($fileA, $mapping)->mappedRows[0]);
        self::assertSame(['student_id' => 'S002'], (new ColumnMapper())->map($fileB, $mapping)->mappedRows[0]);
    }

    public function testMissingCellInARowBecomesAnEmptyString(): void
    {
        $file = new ParsedFile(
            ['รหัสนักเรียน', 'คะแนน'],
            [['S001']], // row shorter than the header (e.g. a trailing blank cell was trimmed)
        );
        $mapping = new ColumnMapping([
            'student_id' => ['รหัสนักเรียน'],
            'score' => ['คะแนน'],
        ]);

        $result = (new ColumnMapper())->map($file, $mapping);

        self::assertSame(['student_id' => 'S001', 'score' => ''], $result->mappedRows[0]);
    }

    public function testEmptyFileProducesNoMappedRows(): void
    {
        $file = new ParsedFile([], []);
        $mapping = new ColumnMapping(['student_id' => ['รหัสนักเรียน']]);

        $result = (new ColumnMapper())->map($file, $mapping);

        self::assertSame([], $result->mappedRows);
        self::assertSame([], $result->unmappedHeaders);
    }
}
