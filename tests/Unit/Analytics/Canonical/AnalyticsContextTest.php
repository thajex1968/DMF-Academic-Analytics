<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Canonical;

use DateTimeImmutable;
use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Canonical\AssessmentAnalyticsRecord;
use DMF\Analytics\Canonical\BenchmarkAnalyticsRecord;
use DMF\Analytics\Canonical\BenchmarkScope;
use DMF\Analytics\Canonical\QuestionAnalyticsRecord;
use DMF\Analytics\Canonical\StandardAnalyticsRecord;
use DMF\Analytics\Canonical\StrandAnalyticsRecord;
use DMF\Analytics\Canonical\SubjectAnalyticsRecord;
use PHPUnit\Framework\TestCase;

final class AnalyticsContextTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $metadata = new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable());
        $assessmentRecord = new AssessmentAnalyticsRecord(3, 30, 120, 96);
        $subjectRecords = [new SubjectAnalyticsRecord('MATH', 30, 120, 96)];
        $strandRecords = [new StrandAnalyticsRecord(10, 'ค1', 'MATH', 20, 80, 60)];
        $standardRecords = [new StandardAnalyticsRecord(100, 'ค1.1', 10, 15, 40, 30)];
        $questionRecords = [new QuestionAnalyticsRecord(1001, 100, 2, 3, 2)];
        $benchmarkRecords = [new BenchmarkAnalyticsRecord(BenchmarkScope::PROVINCE, 'MATH', 0.72)];

        $context = new AnalyticsContext(
            $metadata,
            $assessmentRecord,
            $subjectRecords,
            $strandRecords,
            $standardRecords,
            $questionRecords,
            $benchmarkRecords,
        );

        self::assertSame($metadata, $context->metadata);
        self::assertSame($assessmentRecord, $context->assessmentRecord);
        self::assertSame($subjectRecords, $context->subjectRecords);
        self::assertSame($strandRecords, $context->strandRecords);
        self::assertSame($standardRecords, $context->standardRecords);
        self::assertSame($questionRecords, $context->questionRecords);
        self::assertSame($benchmarkRecords, $context->benchmarkRecords);
    }

    public function testBenchmarkRecordsDefaultsToAnEmptyArrayForPhase1CallSites(): void
    {
        $context = new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 30, 120, 96),
            [],
            [],
            [],
            [],
        );

        self::assertSame([], $context->benchmarkRecords);
    }
}
