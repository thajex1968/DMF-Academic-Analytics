<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\AI\Providers;

use DMF\AI\Contracts\AIProviderInterface;
use DMF\AI\Prompt\Prompt;
use DMF\AI\Prompt\PromptType;
use DMF\AI\Providers\MockProvider;
use PHPUnit\Framework\TestCase;

final class MockProviderTest extends TestCase
{
    private function prompt(PromptType $type): Prompt
    {
        return new Prompt($type, 'ctx', 'stats', 'insights', 'output', 'safety');
    }

    public function testImplementsTheProviderContract(): void
    {
        self::assertInstanceOf(AIProviderInterface::class, new MockProvider());
    }

    public function testIdentityMethodsAreFixed(): void
    {
        $provider = new MockProvider();

        self::assertSame('mock', $provider->providerName());
        self::assertSame('mock-model-v1', $provider->model());
        self::assertSame(['insight', 'recommendation'], $provider->capabilities());
    }

    public function testHealthIsAlwaysTrue(): void
    {
        self::assertTrue((new MockProvider())->health());
    }

    public function testGenerateReturnsAFixedZeroLatencyResponse(): void
    {
        $response = (new MockProvider())->generate($this->prompt(PromptType::INSIGHT));

        self::assertSame('mock', $response->provider);
        self::assertSame('mock-model-v1', $response->model);
        self::assertSame(0.0, $response->latency);
        self::assertSame(128, $response->tokenUsage);
    }

    public function testGenerateIsDeterministicForTheSamePromptType(): void
    {
        $provider = new MockProvider();

        $first = $provider->generate($this->prompt(PromptType::INSIGHT));
        $second = $provider->generate($this->prompt(PromptType::INSIGHT));

        self::assertSame($first->response, $second->response);
    }

    public function testGenerateReturnsValidJsonWithTheRequiredInsightFields(): void
    {
        $response = (new MockProvider())->generate($this->prompt(PromptType::INSIGHT));
        $decoded = json_decode($response->response, true);

        self::assertIsArray($decoded);
        self::assertArrayHasKey('summary', $decoded);
        self::assertArrayHasKey('strengths', $decoded);
        self::assertArrayHasKey('weaknesses', $decoded);
        self::assertArrayHasKey('risks', $decoded);
        self::assertArrayHasKey('confidence', $decoded);
    }

    public function testGenerateReturnsValidJsonWithTheRequiredRecommendationFields(): void
    {
        $response = (new MockProvider())->generate($this->prompt(PromptType::RECOMMENDATION));
        $decoded = json_decode($response->response, true);

        self::assertIsArray($decoded);
        self::assertArrayHasKey('priority', $decoded);
        self::assertArrayHasKey('category', $decoded);
        self::assertArrayHasKey('recommendation', $decoded);
        self::assertArrayHasKey('reason', $decoded);
        self::assertArrayHasKey('confidence', $decoded);
    }
}
