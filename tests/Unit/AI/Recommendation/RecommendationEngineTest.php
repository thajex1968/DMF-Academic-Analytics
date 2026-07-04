<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\AI\Recommendation;

use DMF\AI\Contracts\AIProviderInterface;
use DMF\AI\Contracts\PromptBuilderInterface;
use DMF\AI\DTO\AIContext;
use DMF\AI\DTO\AIRecommendationPriority;
use DMF\AI\DTO\AIResponse;
use DMF\AI\Exceptions\AIException;
use DMF\AI\Exceptions\PromptTooLargeException;
use DMF\AI\Exceptions\UnsupportedProviderException;
use DMF\AI\Prompt\Prompt;
use DMF\AI\Prompt\PromptType;
use DMF\AI\Recommendation\RecommendationEngine;
use DMF\Analytics\Dashboard\DashboardAssessment;
use DMF\Analytics\Dashboard\DashboardHealth;
use DMF\Analytics\Dashboard\DashboardSummary;
use PHPUnit\Framework\TestCase;

final class RecommendationEngineTest extends TestCase
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
        return new Prompt(PromptType::RECOMMENDATION, 'ctx', 'stats', 'insights', 'output', 'safety');
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

        $engine = new RecommendationEngine($promptBuilder, $provider, 10000);

        $recommendation = $engine->generateRecommendation(
            $this->context,
            $this->summary,
            $this->health,
            $this->assessment(null),
        );

        self::assertSame(AIRecommendationPriority::LOW, $recommendation->priority);
        self::assertSame('Insufficient data.', $recommendation->recommendation);
        self::assertSame(0.0, $recommendation->confidence);
    }

    public function testThrowsWhenTheProviderDoesNotSupportRecommendationCapability(): void
    {
        $promptBuilder = $this->createMock(PromptBuilderInterface::class);
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('capabilities')->willReturn(['insight']);
        $provider->method('providerName')->willReturn('limited-mock');

        $engine = new RecommendationEngine($promptBuilder, $provider, 10000);

        $this->expectException(UnsupportedProviderException::class);
        $engine->generateRecommendation($this->context, $this->summary, $this->health, $this->assessment(0.75));
    }

    public function testThrowsWhenTheAssembledPromptExceedsTheConfiguredCharacterLimit(): void
    {
        $promptBuilder = $this->createMock(PromptBuilderInterface::class);
        $promptBuilder->method('build')->willReturn($this->prompt());
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('capabilities')->willReturn(['recommendation']);

        $engine = new RecommendationEngine($promptBuilder, $provider, 1);

        $this->expectException(PromptTooLargeException::class);
        $engine->generateRecommendation($this->context, $this->summary, $this->health, $this->assessment(0.75));
    }

    public function testHappyPathParsesTheProvidersJsonResponseIntoAnAIRecommendation(): void
    {
        $promptBuilder = $this->createMock(PromptBuilderInterface::class);
        $promptBuilder->method('build')->willReturn($this->prompt());
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('capabilities')->willReturn(['recommendation']);
        $providerJson = json_encode([
            'priority' => 'high',
            'category' => 'remediation',
            'recommendation' => 'Schedule a remedial session.',
            'reason' => 'Percent-correct below threshold.',
            'confidence' => 0.7,
        ]);
        $provider->method('generate')->willReturn(new AIResponse('mock', 'mock-model-v1', 0.0, 128, $providerJson));

        $engine = new RecommendationEngine($promptBuilder, $provider, 10000);
        $recommendation = $engine->generateRecommendation(
            $this->context,
            $this->summary,
            $this->health,
            $this->assessment(0.75),
        );

        self::assertSame(AIRecommendationPriority::HIGH, $recommendation->priority);
        self::assertSame('remediation', $recommendation->category);
        self::assertSame('Schedule a remedial session.', $recommendation->recommendation);
        self::assertSame('Percent-correct below threshold.', $recommendation->reason);
        self::assertSame(0.7, $recommendation->confidence);
    }

    public function testThrowsAIExceptionWhenTheProviderResponseIsNotValidJson(): void
    {
        $promptBuilder = $this->createMock(PromptBuilderInterface::class);
        $promptBuilder->method('build')->willReturn($this->prompt());
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('capabilities')->willReturn(['recommendation']);
        $provider->method('generate')->willReturn(new AIResponse('mock', 'mock-model-v1', 0.0, 128, 'not json'));

        $engine = new RecommendationEngine($promptBuilder, $provider, 10000);

        $this->expectException(AIException::class);
        $engine->generateRecommendation($this->context, $this->summary, $this->health, $this->assessment(0.75));
    }

    public function testThrowsAIExceptionWhenTheProviderResponseIsMissingRequiredFields(): void
    {
        $promptBuilder = $this->createMock(PromptBuilderInterface::class);
        $promptBuilder->method('build')->willReturn($this->prompt());
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('capabilities')->willReturn(['recommendation']);
        $providerJson = json_encode(['priority' => 'high']);
        $provider->method('generate')->willReturn(new AIResponse('mock', 'mock-model-v1', 0.0, 128, $providerJson));

        $engine = new RecommendationEngine($promptBuilder, $provider, 10000);

        $this->expectException(AIException::class);
        $engine->generateRecommendation($this->context, $this->summary, $this->health, $this->assessment(0.75));
    }

    public function testThrowsAIExceptionWhenThePriorityValueIsNotOneOfTheRecognizedCases(): void
    {
        $promptBuilder = $this->createMock(PromptBuilderInterface::class);
        $promptBuilder->method('build')->willReturn($this->prompt());
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('capabilities')->willReturn(['recommendation']);
        $providerJson = json_encode([
            'priority' => 'urgent',
            'category' => 'remediation',
            'recommendation' => 'x',
            'reason' => 'y',
            'confidence' => 0.5,
        ]);
        $provider->method('generate')->willReturn(new AIResponse('mock', 'mock-model-v1', 0.0, 128, $providerJson));

        $engine = new RecommendationEngine($promptBuilder, $provider, 10000);

        $this->expectException(AIException::class);
        $engine->generateRecommendation($this->context, $this->summary, $this->health, $this->assessment(0.75));
    }
}
