<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Result;

use DMF\Analytics\Result\DifficultyResult;
use PHPUnit\Framework\TestCase;

final class DifficultyResultTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $result = new DifficultyResult(1001, 100, 0.75);

        self::assertSame(1001, $result->questionId);
        self::assertSame(100, $result->standardId);
        self::assertSame(0.75, $result->difficultyIndex);
    }
}
