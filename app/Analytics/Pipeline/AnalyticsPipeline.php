<?php

declare(strict_types=1);

namespace DMF\Analytics\Pipeline;

use DateTimeImmutable;
use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Contracts\AnalyticsCalculatorInterface;
use DMF\Analytics\Contracts\AnalyticsResultInterface;
use DMF\Analytics\Contracts\CalculatorExecutionContext;

/**
 * Executes every registered calculator against one AnalyticsContext, in
 * CalculatorPriority order (highest first) — never hard-coded registration
 * order, so a future plug-in calculator only needs to declare its own
 * priority, not know where to insert itself in a list. No calculation logic
 * lives here.
 */
final class AnalyticsPipeline
{
    /** @param AnalyticsCalculatorInterface[] $calculators */
    public function __construct(private readonly array $calculators)
    {
    }

    /** @return AnalyticsResultInterface[] */
    public function run(AnalyticsContext $context): array
    {
        $ordered = $this->calculators;
        usort(
            $ordered,
            static fn (AnalyticsCalculatorInterface $a, AnalyticsCalculatorInterface $b): int
                => $b->priority()->value <=> $a->priority()->value,
        );

        $executionContext = new CalculatorExecutionContext($context, new DateTimeImmutable());

        $results = [];
        foreach ($ordered as $calculator) {
            $results[] = $calculator->calculate($executionContext);
        }

        return $results;
    }
}
