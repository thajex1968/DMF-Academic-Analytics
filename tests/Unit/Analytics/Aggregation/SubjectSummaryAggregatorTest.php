<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Aggregation;

use DMF\Analytics\Aggregation\SubjectSummaryAggregator;
use DMF\Analytics\Result\SubjectResult;
use PHPUnit\Framework\TestCase;

final class SubjectSummaryAggregatorTest extends TestCase
{
    public function testReshapesEachSubjectResultIntoADashboardSubject(): void
    {
        $results = [new SubjectResult('MATH', 0.7, null, null, null, null)];

        $subjects = (new SubjectSummaryAggregator())->aggregate($results);

        self::assertCount(1, $subjects);
        self::assertSame('MATH', $subjects[0]->subjectCode);
        self::assertSame(0.7, $subjects[0]->percentCorrect);
    }

    public function testAnEmptyInputProducesAnEmptyOutput(): void
    {
        self::assertSame([], (new SubjectSummaryAggregator())->aggregate([]));
    }
}
