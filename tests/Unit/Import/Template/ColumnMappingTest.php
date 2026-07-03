<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Template;

use DMF\Import\Template\ColumnMapping;
use PHPUnit\Framework\TestCase;

final class ColumnMappingTest extends TestCase
{
    public function testResolvesTheDocumentedFr004ExampleAliasesToStudentId(): void
    {
        // FR-004: "รหัสนักเรียน" / "เลขประจำตัว" both resolve to student ID.
        $mapping = new ColumnMapping([
            'student_id' => ['รหัสนักเรียน', 'เลขประจำตัว'],
        ]);

        self::assertSame('student_id', $mapping->canonicalFieldFor('รหัสนักเรียน'));
        self::assertSame('student_id', $mapping->canonicalFieldFor('เลขประจำตัว'));
    }

    public function testReturnsNullForAnUnrecognizedHeader(): void
    {
        $mapping = new ColumnMapping(['student_id' => ['รหัสนักเรียน']]);

        self::assertNull($mapping->canonicalFieldFor('some unrelated header'));
    }

    public function testTrimsTheHeaderBeforeMatching(): void
    {
        $mapping = new ColumnMapping(['student_id' => ['รหัสนักเรียน']]);

        self::assertSame('student_id', $mapping->canonicalFieldFor('  รหัสนักเรียน  '));
    }

    public function testCanonicalFieldsReturnsEveryKnownField(): void
    {
        $mapping = new ColumnMapping([
            'student_id' => ['รหัสนักเรียน'],
            'full_name' => ['ชื่อ-สกุล'],
        ]);

        self::assertSame(['student_id', 'full_name'], $mapping->canonicalFields());
    }
}
