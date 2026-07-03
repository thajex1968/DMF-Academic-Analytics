<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Template;

use DMF\Import\ParsedFile;
use DMF\Import\Template\ColumnMapper;
use DMF\Import\Template\ExampleTemplates;
use DMF\Import\Template\TemplateValidator;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end: ParsedFile -> ColumnMapper -> TemplateValidator, using the
 * one illustrative example template, exercising the full Parser ->
 * Mapping -> Validation pipeline this Sprint's contracts describe.
 */
final class ExampleTemplatesTest extends TestCase
{
    public function testResolvesEitherFr004DocumentedHeaderAlias(): void
    {
        $template = ExampleTemplates::studentIdOnly();

        $viaFirstAlias = new ParsedFile(['รหัสนักเรียน'], [['S001']]);
        $viaSecondAlias = new ParsedFile(['เลขประจำตัว'], [['S002']]);

        $mapperResultA = (new ColumnMapper())->map($viaFirstAlias, $template->mapping);
        $mapperResultB = (new ColumnMapper())->map($viaSecondAlias, $template->mapping);

        self::assertSame(['student_id' => 'S001'], $mapperResultA->mappedRows[0]);
        self::assertSame(['student_id' => 'S002'], $mapperResultB->mappedRows[0]);
    }

    public function testAFullyPopulatedRowPassesValidation(): void
    {
        $template = ExampleTemplates::studentIdOnly();
        $file = new ParsedFile(['รหัสนักเรียน'], [['S001']]);

        $mapped = (new ColumnMapper())->map($file, $template->mapping);
        $results = (new TemplateValidator())->validate($mapped->mappedRows, $template);

        self::assertTrue($results[0]->passes());
    }

    public function testAMissingStudentIdFailsValidation(): void
    {
        $template = ExampleTemplates::studentIdOnly();
        // A header the mapping doesn't recognize -> student_id never gets populated.
        $file = new ParsedFile(['unrelated_header'], [['x']]);

        $mapped = (new ColumnMapper())->map($file, $template->mapping);
        $results = (new TemplateValidator())->validate($mapped->mappedRows, $template);

        self::assertTrue($results[0]->fails());
        self::assertSame(['unrelated_header'], $mapped->unmappedHeaders);
    }

    public function testKeyIsClearlyMarkedAsAnExampleNotARealProductionTemplate(): void
    {
        self::assertStringContainsString('EXAMPLE', ExampleTemplates::studentIdOnly()->key);
    }
}
