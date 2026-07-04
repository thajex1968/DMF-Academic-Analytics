<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\AI\DTO;

use DMF\AI\DTO\AIContext;
use PHPUnit\Framework\TestCase;

final class AIContextTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $context = new AIContext(3, 1, 'th', 'Asia/Bangkok');

        self::assertSame(3, $context->assessmentId);
        self::assertSame(1, $context->schoolId);
        self::assertSame('th', $context->language);
        self::assertSame('Asia/Bangkok', $context->timezone);
    }
}
