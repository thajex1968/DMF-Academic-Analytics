<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\AI\DTO;

use DMF\AI\DTO\AIRecommendation;
use DMF\AI\DTO\AIRecommendationPriority;
use PHPUnit\Framework\TestCase;

final class AIRecommendationTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $recommendation = new AIRecommendation(
            AIRecommendationPriority::HIGH,
            'remediation',
            'Schedule a remedial session for the weak standard.',
            'Percent-correct is below threshold for this standard.',
            0.8,
        );

        self::assertSame(AIRecommendationPriority::HIGH, $recommendation->priority);
        self::assertSame('remediation', $recommendation->category);
        self::assertSame('Schedule a remedial session for the weak standard.', $recommendation->recommendation);
        self::assertSame('Percent-correct is below threshold for this standard.', $recommendation->reason);
        self::assertSame(0.8, $recommendation->confidence);
    }
}
