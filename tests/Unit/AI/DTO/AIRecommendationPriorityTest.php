<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\AI\DTO;

use DMF\AI\DTO\AIRecommendationPriority;
use PHPUnit\Framework\TestCase;

final class AIRecommendationPriorityTest extends TestCase
{
    public function testEveryCaseHasItsExpectedStringValue(): void
    {
        self::assertSame('high', AIRecommendationPriority::HIGH->value);
        self::assertSame('medium', AIRecommendationPriority::MEDIUM->value);
        self::assertSame('low', AIRecommendationPriority::LOW->value);
    }

    public function testFromRoundTripsEveryValue(): void
    {
        self::assertSame(AIRecommendationPriority::HIGH, AIRecommendationPriority::from('high'));
        self::assertSame(AIRecommendationPriority::MEDIUM, AIRecommendationPriority::from('medium'));
        self::assertSame(AIRecommendationPriority::LOW, AIRecommendationPriority::from('low'));
    }
}
