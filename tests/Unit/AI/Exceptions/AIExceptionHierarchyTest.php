<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\AI\Exceptions;

use DMF\AI\Exceptions\AIException;
use DMF\AI\Exceptions\PromptTooLargeException;
use DMF\AI\Exceptions\UnsupportedProviderException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AIExceptionHierarchyTest extends TestCase
{
    public function testAIExceptionIsARuntimeException(): void
    {
        self::assertInstanceOf(RuntimeException::class, new AIException('x'));
    }

    public function testPromptTooLargeExceptionIsAnAIException(): void
    {
        self::assertInstanceOf(AIException::class, new PromptTooLargeException('x'));
    }

    public function testUnsupportedProviderExceptionIsAnAIException(): void
    {
        self::assertInstanceOf(AIException::class, new UnsupportedProviderException('x'));
    }

    public function testMessagesArePreserved(): void
    {
        self::assertSame('too large', (new PromptTooLargeException('too large'))->getMessage());
        self::assertSame('unsupported', (new UnsupportedProviderException('unsupported'))->getMessage());
    }
}
