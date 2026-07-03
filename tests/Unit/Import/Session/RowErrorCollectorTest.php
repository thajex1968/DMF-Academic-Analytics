<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Session;

use DMF\Import\Score\ImportResult;
use DMF\Import\Session\RowErrorCollector;
use PHPUnit\Framework\TestCase;

final class RowErrorCollectorTest extends TestCase
{
    public function testCollectsRowNumberedErrorsFromAnImportResult(): void
    {
        $result = ImportResult::failure(1, 2, [
            'Row 2: "score" is required but missing or empty.',
            'Row 3: Duplicate student_id "S001" within this file (first seen at row 2).',
        ]);

        $collector = new RowErrorCollector();
        $collector->collectFromImportResult($result);

        $errors = $collector->errors();

        self::assertCount(2, $errors);
        self::assertSame(2, $errors[0]->rowNumber);
        self::assertSame('"score" is required but missing or empty.', $errors[0]->message);
        self::assertSame(3, $errors[1]->rowNumber);
        self::assertSame(
            'Duplicate student_id "S001" within this file (first seen at row 2).',
            $errors[1]->message,
        );
    }

    public function testAWholeFileErrorWithNoRowPrefixIsTraceableAsRowZero(): void
    {
        $result = ImportResult::failure(1, 0, ['Assessment 3 not found.']);

        $collector = new RowErrorCollector();
        $collector->collectFromImportResult($result);

        $errors = $collector->errors();

        self::assertSame(0, $errors[0]->rowNumber);
        self::assertSame('Assessment 3 not found.', $errors[0]->message);
    }

    public function testACommitFailureIsSanitizedAndNeverLeaksTheRawExceptionMessage(): void
    {
        $result = ImportResult::failure(1, 3, [
            'Commit failed: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry',
        ]);

        $collector = new RowErrorCollector();
        $collector->collectFromImportResult($result);

        $message = $collector->errors()[0]->message;

        self::assertStringNotContainsString('SQLSTATE', $message);
        self::assertStringNotContainsString('Integrity constraint', $message);
        self::assertStringContainsString('internal error', $message);
    }

    public function testCollectsFromAPipeJoinedErrorDetailStringAsPersistedByImportJobManager(): void
    {
        $collector = new RowErrorCollector();
        $collector->collectFromRawErrorDetail('Row 2: bad score | Row 4: unknown student_id');

        $errors = $collector->errors();

        self::assertCount(2, $errors);
        self::assertSame(2, $errors[0]->rowNumber);
        self::assertSame('bad score', $errors[0]->message);
        self::assertSame(4, $errors[1]->rowNumber);
        self::assertSame('unknown student_id', $errors[1]->message);
    }
}
