<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\AI\Prompt;

use DMF\AI\Prompt\Prompt;
use DMF\AI\Prompt\PromptType;
use PHPUnit\Framework\TestCase;

final class PromptTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $prompt = new Prompt(
            PromptType::INSIGHT,
            'context section',
            'statistics section',
            'insights section',
            'required output section',
            'safety rules section',
        );

        self::assertSame(PromptType::INSIGHT, $prompt->type);
        self::assertSame('context section', $prompt->context);
        self::assertSame('statistics section', $prompt->statistics);
        self::assertSame('insights section', $prompt->insights);
        self::assertSame('required output section', $prompt->requiredOutput);
        self::assertSame('safety rules section', $prompt->safetyRules);
    }

    public function testToTextAssemblesEverySectionInOrderWithHeadings(): void
    {
        $prompt = new Prompt(
            PromptType::RECOMMENDATION,
            'CTX',
            'STATS',
            'INSIGHTS',
            'OUTPUT',
            'SAFETY',
        );

        $text = $prompt->toText();

        self::assertStringContainsString("## Context\nCTX", $text);
        self::assertStringContainsString("## Statistics\nSTATS", $text);
        self::assertStringContainsString("## Insights\nINSIGHTS", $text);
        self::assertStringContainsString("## Required Output\nOUTPUT", $text);
        self::assertStringContainsString("## Safety Rules\nSAFETY", $text);

        self::assertLessThan(
            strpos($text, 'STATS'),
            strpos($text, 'CTX'),
            'Context must appear before Statistics',
        );
        self::assertLessThan(
            strpos($text, 'SAFETY'),
            strpos($text, 'OUTPUT'),
            'Required Output must appear before Safety Rules',
        );
    }
}
