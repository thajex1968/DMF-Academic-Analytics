<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Result;

use DMF\Analytics\Result\AnalyticsWarning;
use PHPUnit\Framework\TestCase;

final class AnalyticsWarningTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $warning = new AnalyticsWarning('question:1001', 'Sample size below the recommended minimum.');

        self::assertSame('question:1001', $warning->identifier);
        self::assertSame('Sample size below the recommended minimum.', $warning->message);
    }
}
