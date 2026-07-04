<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\AI\DTO;

use DMF\AI\DTO\AIInsight;
use PHPUnit\Framework\TestCase;

final class AIInsightTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $insight = new AIInsight(
            'Overall performance is steady.',
            ['Strong in MATH'],
            ['Weak in SCI'],
            ['Sample size is small'],
            0.72,
        );

        self::assertSame('Overall performance is steady.', $insight->summary);
        self::assertSame(['Strong in MATH'], $insight->strengths);
        self::assertSame(['Weak in SCI'], $insight->weaknesses);
        self::assertSame(['Sample size is small'], $insight->risks);
        self::assertSame(0.72, $insight->confidence);
    }

    public function testStrengthsWeaknessesAndRisksAcceptEmptyArrays(): void
    {
        $insight = new AIInsight('Insufficient data.', [], [], [], 0.0);

        self::assertSame([], $insight->strengths);
        self::assertSame([], $insight->weaknesses);
        self::assertSame([], $insight->risks);
    }
}
