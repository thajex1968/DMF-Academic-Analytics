<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\AI\Prompt;

use DMF\AI\Prompt\PromptType;
use PHPUnit\Framework\TestCase;

final class PromptTypeTest extends TestCase
{
    public function testEveryCaseHasItsExpectedStringValue(): void
    {
        self::assertSame('insight', PromptType::INSIGHT->value);
        self::assertSame('recommendation', PromptType::RECOMMENDATION->value);
    }
}
