<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Template;

use DMF\Import\Template\ColumnMapping;
use DMF\Import\Template\ImportTemplate;
use DMF\Import\Template\TemplateValidator;
use PHPUnit\Framework\TestCase;

final class TemplateValidatorTest extends TestCase
{
    public function testPassesWhenEveryRequiredColumnIsPresent(): void
    {
        $template = $this->makeTemplate(requiredColumns: ['student_id']);
        $results = (new TemplateValidator())->validate([['student_id' => 'S001']], $template);

        self::assertTrue($results[0]->passes());
    }

    public function testFailsWhenARequiredColumnIsMissing(): void
    {
        $template = $this->makeTemplate(requiredColumns: ['student_id', 'score']);
        $results = (new TemplateValidator())->validate([['student_id' => 'S001']], $template);

        self::assertTrue($results[0]->fails());
        self::assertSame('"score" is required but missing or empty.', $results[0]->firstError('score'));
    }

    public function testFailsWhenARequiredColumnIsPresentButEmpty(): void
    {
        $template = $this->makeTemplate(requiredColumns: ['student_id']);
        $results = (new TemplateValidator())->validate([['student_id' => '   ']], $template);

        self::assertTrue($results[0]->fails());
    }

    public function testRunsDmfCoreValidatorRulesAndSurfacesTheirErrors(): void
    {
        $template = $this->makeTemplate(
            requiredColumns: ['student_id'],
            validationRules: ['student_id' => 'required|max:3'],
        );

        $results = (new TemplateValidator())->validate([['student_id' => 'TOOLONG']], $template);

        self::assertTrue($results[0]->fails());
    }

    public function testValidatesEachRowIndependentlyKeyedByRowIndex(): void
    {
        $template = $this->makeTemplate(requiredColumns: ['student_id']);

        $results = (new TemplateValidator())->validate(
            [['student_id' => 'S001'], ['student_id' => '']],
            $template,
        );

        self::assertTrue($results[0]->passes());
        self::assertTrue($results[1]->fails());
    }

    public function testSkipsDmfCoreValidatorEntirelyWhenNoRulesAreConfigured(): void
    {
        $template = $this->makeTemplate(requiredColumns: [], validationRules: []);

        $results = (new TemplateValidator())->validate([['anything' => 'x']], $template);

        self::assertTrue($results[0]->passes());
    }

    /**
     * @param string[] $requiredColumns
     * @param array<string, string> $validationRules
     */
    private function makeTemplate(array $requiredColumns, array $validationRules = []): ImportTemplate
    {
        return new ImportTemplate(
            key: 'TEST-TEMPLATE',
            mappingVersion: 'v1',
            mapping: new ColumnMapping(['student_id' => ['รหัสนักเรียน']]),
            requiredColumns: $requiredColumns,
            optionalColumns: [],
            validationRules: $validationRules,
        );
    }
}
