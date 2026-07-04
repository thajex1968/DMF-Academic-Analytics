<?php

declare(strict_types=1);

namespace DMF\AI\Prompt;

/**
 * The structured output every PromptBuilderInterface implementation
 * produces — five named sections (Context, Statistics, Insights, Required
 * Output, Safety Rules), never ad hoc string concatenation scattered
 * across engines. `toText()` is the one place these sections are joined
 * into what an AIProviderInterface actually sends to a provider.
 */
final class Prompt
{
    public function __construct(
        public readonly PromptType $type,
        public readonly string $context,
        public readonly string $statistics,
        public readonly string $insights,
        public readonly string $requiredOutput,
        public readonly string $safetyRules,
    ) {
    }

    public function toText(): string
    {
        return implode("\n\n", [
            "## Context\n" . $this->context,
            "## Statistics\n" . $this->statistics,
            "## Insights\n" . $this->insights,
            "## Required Output\n" . $this->requiredOutput,
            "## Safety Rules\n" . $this->safetyRules,
        ]);
    }
}
