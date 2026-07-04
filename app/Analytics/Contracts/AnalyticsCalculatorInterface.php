<?php

declare(strict_types=1);

namespace DMF\Analytics\Contracts;

/**
 * One Analytics computation over a Canonical AnalyticsContext, wrapped in a
 * CalculatorExecutionContext for shared per-run metadata (Sprint 4 Phase 2).
 * An implementation must never inspect assessment type, source name,
 * provider, or report format — only the Assessment Adapter Layer knows
 * those (docs/02-System-Architecture.md §8.1). `priority()` governs
 * AnalyticsPipeline's execution order, never a data dependency between
 * calculators — every calculator remains independently executable.
 */
interface AnalyticsCalculatorInterface
{
    public function name(): string;

    public function priority(): CalculatorPriority;

    public function capabilities(): CalculatorCapabilities;

    public function calculate(CalculatorExecutionContext $executionContext): AnalyticsResultInterface;
}
