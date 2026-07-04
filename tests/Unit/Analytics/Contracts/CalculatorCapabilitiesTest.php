<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Contracts;

use DMF\Analytics\Contracts\CalculatorCapabilities;
use PHPUnit\Framework\TestCase;

final class CalculatorCapabilitiesTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $capabilities = new CalculatorCapabilities(true, false);

        self::assertTrue($capabilities->supportsLevel1);
        self::assertFalse($capabilities->supportsLevel2);
    }
}
