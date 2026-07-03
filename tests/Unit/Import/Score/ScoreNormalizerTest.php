<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Score;

use DMF\Import\Score\ScoreNormalizer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ScoreNormalizerTest extends TestCase
{
    public function testNormalizesAPlainNumericString(): void
    {
        self::assertSame(87.5, (new ScoreNormalizer())->normalize('87.5'));
    }

    public function testTrimsWhitespace(): void
    {
        self::assertSame(92.0, (new ScoreNormalizer())->normalize('  92  '));
    }

    public function testRoundsToTwoDecimalPlaces(): void
    {
        self::assertSame(65.26, (new ScoreNormalizer())->normalize('65.255'));
    }

    public function testAcceptsTheBoundaryValuesZeroAndOneHundred(): void
    {
        self::assertSame(0.0, (new ScoreNormalizer())->normalize('0'));
        self::assertSame(100.0, (new ScoreNormalizer())->normalize('100'));
    }

    public function testRejectsAScoreAboveOneHundred(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('outside the valid 0.00–100.00 range');

        (new ScoreNormalizer())->normalize('150');
    }

    public function testRejectsABoundaryOverflowCausedByDecimalPrecision(): void
    {
        // Verified real gap in Dmf\Core\Validation\Rules\IntRangeRule: it casts to (int) before
        // comparing, so "100.5" would incorrectly pass a 0-100 int_range check (truncates to 100).
        // ScoreNormalizer must reject it using real float comparison instead.
        $this->expectException(InvalidArgumentException::class);

        (new ScoreNormalizer())->normalize('100.5');
    }

    public function testRejectsANegativeScore(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ScoreNormalizer())->normalize('-5');
    }

    public function testRejectsANonNumericValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not a valid numeric score');

        (new ScoreNormalizer())->normalize('abc');
    }

    public function testRejectsAnEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ScoreNormalizer())->normalize('');
    }

    public function testRejectsAWhitespaceOnlyString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ScoreNormalizer())->normalize('   ');
    }
}
