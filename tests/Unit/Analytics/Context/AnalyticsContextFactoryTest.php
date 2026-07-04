<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Context;

use DateTimeImmutable;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Context\AnalyticsContextFactory;
use DMF\Analytics\Normalization\NormalizationResult;
use DMF\Analytics\Normalization\NormalizedRecord;
use DMF\Analytics\Normalization\NormalizedStandardMapping;
use DMF\Analytics\Normalization\ResolvedIndicator;
use DMF\Analytics\Normalization\ResolvedStandard;
use DMF\Analytics\Normalization\ResolvedStrand;
use PHPUnit\Framework\TestCase;

/**
 * Exercises AnalyticsContextFactory's grouping/tallying against a small,
 * hand-built Canonical fixture (NormalizedRecord[]) engineered to cover
 * every grain transition: two questions under one standard, two standards
 * under one strand, two strands under one subject, and a student answering
 * more than one question so studentCount (distinct) and responseCount
 * (total) genuinely differ.
 *
 * Fixture shape:
 *   Strand A (id 10, ST-A, subject MATH)
 *     Standard A1 (id 100) -- Question 1, Question 2
 *     Standard A2 (id 101) -- Question 3
 *   Strand B (id 11, ST-B, subject MATH)
 *     Standard B1 (id 110) -- Question 4
 */
final class AnalyticsContextFactoryTest extends TestCase
{
    private ResolvedStandard $standardA1;
    private ResolvedStandard $standardA2;
    private ResolvedStandard $standardB1;

    protected function setUp(): void
    {
        $strandA = new ResolvedStrand(10, 'MATH', 'ST-A', 'สาระทดสอบ A');
        $strandB = new ResolvedStrand(11, 'MATH', 'ST-B', 'สาระทดสอบ B');

        $this->standardA1 = new ResolvedStandard(100, 'STD-A1', 'มาตรฐานทดสอบ A1', $strandA);
        $this->standardA2 = new ResolvedStandard(101, 'STD-A2', 'มาตรฐานทดสอบ A2', $strandA);
        $this->standardB1 = new ResolvedStandard(110, 'STD-B1', 'มาตรฐานทดสอบ B1', $strandB);
    }

    private function record(
        string $studentId,
        int $questionId,
        ResolvedStandard $standard,
        bool $isCorrect,
    ): NormalizedRecord {
        $indicator = new ResolvedIndicator(
            $questionId * 1000,
            "IND-{$questionId}",
            'ตัวชี้วัดทดสอบ',
            6,
            '2560',
            $standard,
        );
        $mapping = new NormalizedStandardMapping($questionId, $indicator, []);

        return new NormalizedRecord($studentId, $mapping, '1', $isCorrect);
    }

    private function metadata(): AnalyticsMetadata
    {
        return new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable('2026-07-04T09:00:00+07:00'));
    }

    public function testAnEmptyNormalizationResultProducesAnEmptyContextWithZeroCounts(): void
    {
        $context = (new AnalyticsContextFactory())->fromNormalizationResult(
            NormalizationResult::build([], [], [], 0),
            $this->metadata(),
        );

        self::assertSame(3, $context->assessmentRecord->assessmentId);
        self::assertSame(0, $context->assessmentRecord->studentCount);
        self::assertSame(0, $context->assessmentRecord->responseCount);
        self::assertSame(0, $context->assessmentRecord->correctCount);
        self::assertSame([], $context->subjectRecords);
        self::assertSame([], $context->strandRecords);
        self::assertSame([], $context->standardRecords);
        self::assertSame([], $context->questionRecords);
    }

    public function testGroupsAndTalliesAcrossEveryCanonicalGrain(): void
    {
        $records = [
            $this->record('S1', 1, $this->standardA1, true),
            $this->record('S2', 1, $this->standardA1, false),
            $this->record('S1', 2, $this->standardA1, true),
            $this->record('S3', 3, $this->standardA2, true),
            $this->record('S1', 4, $this->standardB1, false),
        ];

        $context = (new AnalyticsContextFactory())->fromNormalizationResult(
            NormalizationResult::build($records, [], [], count($records)),
            $this->metadata(),
        );

        // Assessment grain: 3 distinct students (S1, S2, S3), 5 responses, 3 correct.
        self::assertSame(3, $context->assessmentRecord->assessmentId);
        self::assertSame(3, $context->assessmentRecord->studentCount);
        self::assertSame(5, $context->assessmentRecord->responseCount);
        self::assertSame(3, $context->assessmentRecord->correctCount);

        // Subject grain: one subject (MATH), same totals as the assessment.
        self::assertCount(1, $context->subjectRecords);
        self::assertSame('MATH', $context->subjectRecords[0]->subjectCode);
        self::assertSame(3, $context->subjectRecords[0]->studentCount);
        self::assertSame(5, $context->subjectRecords[0]->responseCount);
        self::assertSame(3, $context->subjectRecords[0]->correctCount);

        // Strand grain: Strand A (Q1, Q2, Q3) and Strand B (Q4).
        self::assertCount(2, $context->strandRecords);
        $strandA = $context->strandRecords[0];
        self::assertSame(10, $strandA->strandId);
        self::assertSame('ST-A', $strandA->strandCode);
        self::assertSame('MATH', $strandA->subjectCode);
        self::assertSame(3, $strandA->studentCount);
        self::assertSame(4, $strandA->responseCount);
        self::assertSame(3, $strandA->correctCount);

        $strandB = $context->strandRecords[1];
        self::assertSame(11, $strandB->strandId);
        self::assertSame(1, $strandB->studentCount);
        self::assertSame(1, $strandB->responseCount);
        self::assertSame(0, $strandB->correctCount);

        // Standard grain: A1 (Q1, Q2), A2 (Q3), B1 (Q4).
        self::assertCount(3, $context->standardRecords);
        $standardA1 = $context->standardRecords[0];
        self::assertSame(100, $standardA1->standardId);
        self::assertSame('STD-A1', $standardA1->standardCode);
        self::assertSame(10, $standardA1->strandId);
        self::assertSame(2, $standardA1->studentCount, 'S1 and S2 answered a Standard A1 question');
        self::assertSame(3, $standardA1->responseCount, 'Q1 (x2 students) + Q2 (x1 student)');
        self::assertSame(2, $standardA1->correctCount);

        $standardA2 = $context->standardRecords[1];
        self::assertSame(101, $standardA2->standardId);
        self::assertSame(1, $standardA2->studentCount);
        self::assertSame(1, $standardA2->responseCount);
        self::assertSame(1, $standardA2->correctCount);

        $standardB1 = $context->standardRecords[2];
        self::assertSame(110, $standardB1->standardId);
        self::assertSame(1, $standardB1->studentCount);
        self::assertSame(1, $standardB1->responseCount);
        self::assertSame(0, $standardB1->correctCount);

        // Question grain: one record per question, never merged across questions.
        self::assertCount(4, $context->questionRecords);
        self::assertSame(1, $context->questionRecords[0]->questionId);
        self::assertSame(100, $context->questionRecords[0]->standardId);
        self::assertSame(2, $context->questionRecords[0]->studentCount, 'Q1 answered by S1 and S2');
        self::assertSame(2, $context->questionRecords[0]->responseCount);
        self::assertSame(1, $context->questionRecords[0]->correctCount);

        self::assertSame(2, $context->questionRecords[1]->questionId);
        self::assertSame(1, $context->questionRecords[1]->studentCount);
        self::assertSame(1, $context->questionRecords[1]->correctCount);

        self::assertSame(3, $context->questionRecords[2]->questionId);
        self::assertSame(101, $context->questionRecords[2]->standardId);

        self::assertSame(4, $context->questionRecords[3]->questionId);
        self::assertSame(110, $context->questionRecords[3]->standardId);
        self::assertSame(0, $context->questionRecords[3]->correctCount);
    }

    public function testMetadataIsCarriedThroughUnchanged(): void
    {
        $metadata = $this->metadata();

        $context = (new AnalyticsContextFactory())->fromNormalizationResult(
            NormalizationResult::build([], [], [], 0),
            $metadata,
        );

        self::assertSame($metadata, $context->metadata);
    }
}
