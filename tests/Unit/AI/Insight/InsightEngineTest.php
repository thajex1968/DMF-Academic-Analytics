<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\AI\Insight;

use DMF\AI\Contracts\AIProviderInterface;
use DMF\AI\Contracts\PromptBuilderInterface;
use DMF\AI\DTO\AIContext;
use DMF\AI\DTO\AIResponse;
use DMF\AI\Exceptions\AIException;
use DMF\AI\Exceptions\PromptTooLargeException;
use DMF\AI\Exceptions\UnsupportedProviderException;
use DMF\AI\Insight\InsightEngine;
use DMF\AI\Prompt\Prompt;
use DMF\AI\Prompt\PromptType;
use DMF\Analytics\Dashboard\DashboardAssessment;
use DMF\Analytics\Dashboard\DashboardHealth;
use DMF\Analytics\Dashboard\DashboardSummary;
use PHPUnit\Framework\TestCase;

final class InsightEngineTest extends TestCase
{
    private AIContext $context;
    private DashboardSummary $summary;
    private DashboardHealth $health;

    protected function setUp(): void
    {
        $this->context = new AIContext(3, 1, 'th', 'Asia/Bangkok');
        $this->summary = new DashboardSummary(0.75, 30, 120, [], []);
        $this->health = new DashboardHealth('ok', 'ok', 3, 'MATH', 2569, null, 30, 1, []);
    }

    private function prompt(): Prompt
    {
        return new Prompt(PromptType::INSIGHT, 'ctx', 'stats', 'insights', 'output', 'safety');
    }

    private function assessment(?float $percentCorrect): DashboardAssessment
    {
        return new DashboardAssessment(3, 30, 120, 90, $percentCorrect);
    }

    public function testInsufficientDataShortCircuitsWithoutBuildingAPromptOrCallingTheProvider(): void
    {
        $promptBuilder = $this->createMock(PromptBuilderInterface::class);
        $promptBuilder->expects(self::never())->method('build');
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->expects(self::never())->method('generate');

        $engine = new InsightEngine($promptBuilder, $provider, 10000);

        $insight = $engine->generateInsight($this->context, $this->summary, $this->health, $this->assessment(null));

        self::assertSame('Insufficient data.', $insight->summary);
        self::assertSame([], $insight->strengths);
        self::assertSame(0.0, $insight->confidence);
    }

    public function testThrowsWhenTheProviderDoesNotSupportInsightCapability(): void
    {
        $promptBuilder = $this->createMock(PromptBuilderInterface::class);
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('capabilities')->willReturn(['recommendation']);
        $provider->method('providerName')->willReturn('limited-mock');

        $engine = new InsightEngine($promptBuilder, $provider, 10000);

        $this->expectException(UnsupportedProviderException::class);
        $engine->generateInsight($this->context, $this->summary, $this->health, $this->assessment(0.75));
    }

    public function testThrowsWhenTheAssembledPromptExceedsTheConfiguredCharacterLimit(): void
    {
        $promptBuilder = $this->createMock(PromptBuilderInterface::class);
        $promptBuilder->method('build')->willReturn($this->prompt());
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('capabilities')->willReturn(['insight']);

        $engine = new InsightEngine($promptBuilder, $provider, 1); // 1 char — any real prompt exceeds it.

        $this->expectException(PromptTooLargeException::class);
        $engine->generateInsight($this->context, $this->summary, $this->health, $this->assessment(0.75));
    }

    public function testHappyPathParsesTheProvidersJsonResponseIntoAnAIInsight(): void
    {
        $promptBuilder = $this->createMock(PromptBuilderInterface::class);
        $promptBuilder->method('build')->willReturn($this->prompt());
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('capabilities')->willReturn(['insight']);
        $providerJson = json_encode([
            'summary' => 'Steady performance.',
            'strengths' => ['MATH'],
            'weaknesses' => ['SCI'],
            'risks' => ['Small sample'],
            'confidence' => 0.6,
        ]);
        $provider->method('generate')->willReturn(new AIResponse('mock', 'mock-model-v1', 0.0, 128, $providerJson));

        $engine = new InsightEngine($promptBuilder, $provider, 10000);
        $insight = $engine->generateInsight($this->context, $this->summary, $this->health, $this->assessment(0.75));

        self::assertSame('Steady performance.', $insight->summary);
        self::assertSame(['MATH'], $insight->strengths);
        self::assertSame(['SCI'], $insight->weaknesses);
        self::assertSame(['Small sample'], $insight->risks);
        self::assertSame(0.6, $insight->confidence);
    }

    public function testThrowsAIExceptionWhenTheProviderResponseIsNotValidJson(): void
    {
        $promptBuilder = $this->createMock(PromptBuilderInterface::class);
        $promptBuilder->method('build')->willReturn($this->prompt());
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('capabilities')->willReturn(['insight']);
        $provider->method('generate')->willReturn(new AIResponse('mock', 'mock-model-v1', 0.0, 128, 'not json'));

        $engine = new InsightEngine($promptBuilder, $provider, 10000);

        $this->expectException(AIException::class);
        $engine->generateInsight($this->context, $this->summary, $this->health, $this->assessment(0.75));
    }

    public function testThrowsAIExceptionWhenTheProviderResponseIsMissingRequiredFields(): void
    {
        $promptBuilder = $this->createMock(PromptBuilderInterface::class);
        $promptBuilder->method('build')->willReturn($this->prompt());
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('capabilities')->willReturn(['insight']);
        $providerJson = json_encode(['summary' => 'x']);
        $provider->method('generate')->willReturn(new AIResponse('mock', 'mock-model-v1', 0.0, 128, $providerJson));

        $engine = new InsightEngine($promptBuilder, $provider, 10000);

        $this->expectException(AIException::class);
        $engine->generateInsight($this->context, $this->summary, $this->health, $this->assessment(0.75));
    }
}
