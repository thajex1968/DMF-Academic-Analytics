<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Score;

use DMF\Import\Score\RowValidator;
use DMF\Import\Template\ExampleTemplates;
use DMF\Import\Template\TemplateValidator;
use PHPUnit\Framework\TestCase;

final class RowValidatorTest extends TestCase
{
    public function testPassesWhenEveryRowIsValidAndUnique(): void
    {
        $template = ExampleTemplates::studentIdAndScore();
        $rows = [
            ['student_id' => 'S001', 'score' => '87.5'],
            ['student_id' => 'S002', 'score' => '92'],
        ];

        $results = (new RowValidator(new TemplateValidator()))->validate($rows, $template);

        self::assertTrue($results[0]->passes());
        self::assertTrue($results[1]->passes());
    }

    public function testFlagsASecondRowWithADuplicateStudentId(): void
    {
        $template = ExampleTemplates::studentIdAndScore();
        $rows = [
            ['student_id' => 'S001', 'score' => '87.5'],
            ['student_id' => 'S001', 'score' => '92'],
        ];

        $results = (new RowValidator(new TemplateValidator()))->validate($rows, $template);

        self::assertTrue($results[0]->passes(), 'the first occurrence is not itself an error');
        self::assertTrue($results[1]->fails());
        self::assertStringContainsString(
            'Duplicate student_id "S001"',
            $results[1]->firstError('student_id') ?? '',
        );
    }

    public function testDoesNotFlagTwoRowsWithBlankStudentIdAsDuplicatesOfEachOther(): void
    {
        $template = ExampleTemplates::studentIdAndScore();
        $rows = [
            ['student_id' => '', 'score' => '87.5'],
            ['student_id' => '', 'score' => '92'],
        ];

        $results = (new RowValidator(new TemplateValidator()))->validate($rows, $template);

        // Both still fail the required-column check, but not for "duplicate" reasons —
        // a blank student_id is never tracked as "already seen".
        self::assertTrue($results[0]->fails());
        self::assertTrue($results[1]->fails());

        foreach ($results[1]->errors() as $messages) {
            foreach ($messages as $message) {
                self::assertStringNotContainsString('Duplicate', $message);
            }
        }
    }

    public function testStillAppliesTemplateValidationRulesFromTheInjectedValidator(): void
    {
        $template = ExampleTemplates::studentIdAndScore();
        $rows = [['student_id' => '', 'score' => '87.5']];

        $results = (new RowValidator(new TemplateValidator()))->validate($rows, $template);

        self::assertTrue($results[0]->fails());
        self::assertStringContainsString('required', $results[0]->firstError('student_id') ?? '');
    }
}
