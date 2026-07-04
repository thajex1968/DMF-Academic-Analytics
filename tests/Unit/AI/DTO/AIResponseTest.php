<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\AI\DTO;

use DMF\AI\DTO\AIResponse;
use PHPUnit\Framework\TestCase;

final class AIResponseTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $response = new AIResponse('mock', 'mock-model-v1', 0.0, 128, '{"summary":"ok"}');

        self::assertSame('mock', $response->provider);
        self::assertSame('mock-model-v1', $response->model);
        self::assertSame(0.0, $response->latency);
        self::assertSame(128, $response->tokenUsage);
        self::assertSame('{"summary":"ok"}', $response->response);
    }
}
