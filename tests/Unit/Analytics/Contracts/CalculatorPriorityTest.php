<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Contracts;

use DMF\Analytics\Contracts\CalculatorPriority;
use PHPUnit\Framework\TestCase;

final class CalculatorPriorityTest extends TestCase
{
    public function testCasesAreOrderedFromHighestToLowestByValue(): void
    {
        self::assertGreaterThan(CalculatorPriority::HIGH->value, CalculatorPriority::HIGHEST->value);
        self::assertGreaterThan(CalculatorPriority::NORMAL->value, CalculatorPriority::HIGH->value);
        self::assertGreaterThan(CalculatorPriority::LOW->value, CalculatorPriority::NORMAL->value);
        self::assertGreaterThan(CalculatorPriority::LOWEST->value, CalculatorPriority::LOW->value);
    }
}
