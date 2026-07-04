<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Result;

use DateTimeImmutable;
use DMF\Analytics\Contracts\AnalyticsResultInterface;
use DMF\Analytics\Result\AnalyticsIssue;
use DMF\Analytics\Result\AnalyticsResult;
use DMF\Analytics\Result\AnalyticsWarning;
use PHPUnit\Framework\TestCase;

final class AnalyticsResultTest extends TestCase
{
    public function testBuildDerivesASummaryFromTheSuppliedRecordsWarningsAndIssues(): void
    {
        $computedAt = new DateTimeImmutable('2026-07-04T09:00:00+07:00');
        $records = ['placeholder-record-a', 'placeholder-record-b'];
        $warnings = [new AnalyticsWarning('question:1001', 'Sample size below the recommended minimum.')];
        $issues = [
            new AnalyticsIssue('question:1002', 'No responses available for this question.'),
            new AnalyticsIssue('question:1003', 'No responses available for this question.'),
        ];

        $result = AnalyticsResult::build('placeholder-calculator', $records, $warnings, $issues, $computedAt);

        self::assertInstanceOf(AnalyticsResultInterface::class, $result);
        self::assertSame('placeholder-calculator', $result->calculatorName());
        self::assertSame($records, $result->records());
        self::assertSame($warnings, $result->warnings());
        self::assertSame($issues, $result->issues());

        $summary = $result->summary();
        self::assertSame('placeholder-calculator', $summary->calculatorName);
        self::assertSame(2, $summary->recordCount);
        self::assertSame(2, $summary->issueCount);
        self::assertSame(1, $summary->warningCount);
        self::assertSame($computedAt, $summary->computedAt);
    }

    public function testBuildWithNoRecordsWarningsOrIssuesProducesEmptyCollectionsAndZeroCounts(): void
    {
        $result = AnalyticsResult::build('placeholder-calculator', [], [], [], new DateTimeImmutable());

        self::assertSame([], $result->records());
        self::assertSame([], $result->warnings());
        self::assertSame([], $result->issues());
        self::assertSame(0, $result->summary()->recordCount);
        self::assertSame(0, $result->summary()->issueCount);
        self::assertSame(0, $result->summary()->warningCount);
    }
}
