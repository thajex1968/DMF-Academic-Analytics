<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\AI\Prompt;

use DMF\AI\DTO\AIContext;
use DMF\AI\Prompt\DashboardPromptBuilder;
use DMF\AI\Prompt\PromptType;
use DMF\Analytics\Dashboard\DashboardAssessment;
use DMF\Analytics\Dashboard\DashboardHealth;
use DMF\Analytics\Dashboard\DashboardSummary;
use PHPUnit\Framework\TestCase;

final class DashboardPromptBuilderTest extends TestCase
{
    private function context(): AIContext
    {
        return new AIContext(3, 1, 'th', 'Asia/Bangkok');
    }

    private function summary(?float $overallPercentCorrect): DashboardSummary
    {
        return new DashboardSummary($overallPercentCorrect, 30, 120, [], []);
    }

    private function health(): DashboardHealth
    {
        return new DashboardHealth('ok', 'ok', 3, 'MATH', 2569, null, 30, 1, []);
    }

    private function assessment(?float $percentCorrect): DashboardAssessment
    {
        return new DashboardAssessment(3, 30, 120, 90, $percentCorrect);
    }

    public function testBuildProducesAPromptCarryingTheRequestedType(): void
    {
        $prompt = (new DashboardPromptBuilder())->build(
            PromptType::INSIGHT,
            $this->context(),
            $this->summary(0.75),
            $this->health(),
            $this->assessment(0.75),
        );

        self::assertSame(PromptType::INSIGHT, $prompt->type);
    }

    public function testStatisticsSectionReflectsTheSuppliedFigures(): void
    {
        $prompt = (new DashboardPromptBuilder())->build(
            PromptType::INSIGHT,
            $this->context(),
            $this->summary(0.75),
            $this->health(),
            $this->assessment(0.75),
        );

        self::assertStringContainsString('Total students: 30', $prompt->statistics);
        self::assertStringContainsString('Total responses: 120', $prompt->statistics);
        self::assertStringContainsString('Correct responses: 90', $prompt->statistics);
        self::assertStringContainsString('75.0%', $prompt->statistics);
    }

    public function testStatisticsSectionReportsMissingDataHonestlyRatherThanAZeroOrBlank(): void
    {
        $prompt = (new DashboardPromptBuilder())->build(
            PromptType::INSIGHT,
            $this->context(),
            $this->summary(null),
            $this->health(),
            $this->assessment(null),
        );

        self::assertStringContainsString('not available', $prompt->statistics);
    }

    public function testInsightsSectionReflectsHealthWarnings(): void
    {
        $prompt = (new DashboardPromptBuilder())->build(
            PromptType::INSIGHT,
            $this->context(),
            $this->summary(0.75),
            $this->health(),
            $this->assessment(0.75),
        );

        self::assertStringContainsString('Warnings: none', $prompt->insights);
        self::assertStringContainsString('Import status: ok', $prompt->insights);
    }

    public function testRequiredOutputSectionDiffersByPromptType(): void
    {
        $insightPrompt = (new DashboardPromptBuilder())->build(
            PromptType::INSIGHT,
            $this->context(),
            $this->summary(0.75),
            $this->health(),
            $this->assessment(0.75),
        );
        $recommendationPrompt = (new DashboardPromptBuilder())->build(
            PromptType::RECOMMENDATION,
            $this->context(),
            $this->summary(0.75),
            $this->health(),
            $this->assessment(0.75),
        );

        self::assertStringContainsString('"summary"', $insightPrompt->requiredOutput);
        self::assertStringContainsString('"priority"', $recommendationPrompt->requiredOutput);
    }

    public function testSafetyRulesSectionForbidsFabrication(): void
    {
        $prompt = (new DashboardPromptBuilder())->build(
            PromptType::INSIGHT,
            $this->context(),
            $this->summary(0.75),
            $this->health(),
            $this->assessment(0.75),
        );

        self::assertStringContainsString('Never fabricate', $prompt->safetyRules);
        self::assertStringContainsString('Insufficient data', $prompt->safetyRules);
    }
}
