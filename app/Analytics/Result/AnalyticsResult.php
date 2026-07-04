<?php

declare(strict_types=1);

namespace DMF\Analytics\Result;

use DateTimeImmutable;
use DMF\Analytics\Contracts\AnalyticsResultInterface;

/**
 * The outcome of one AnalyticsCalculatorInterface::calculate() run. Pure
 * DTO — carries only what the calculator itself already produced; no
 * dashboard formatting, no presentation-layer shaping.
 *
 * `$records` holds whichever calculator-specific record type produced this
 * result (e.g. DifficultyResult[], StandardResult[] — Sprint 4 Phase 2).
 * Phase 1 had no way to carry a calculator's actual computed output, only
 * its summary counts; this is an additive extension, not a redesign —
 * every Phase 1 accessor (calculatorName/warnings/issues/summary) is
 * unchanged.
 */
final class AnalyticsResult implements AnalyticsResultInterface
{
    /**
     * @param mixed[] $records
     * @param AnalyticsWarning[] $warnings
     * @param AnalyticsIssue[] $issues
     */
    public function __construct(
        private readonly string $calculatorName,
        private readonly array $records,
        private readonly array $warnings,
        private readonly array $issues,
        private readonly AnalyticsSummary $summary,
    ) {
    }

    /**
     * @param mixed[] $records
     * @param AnalyticsWarning[] $warnings
     * @param AnalyticsIssue[] $issues
     */
    public static function build(
        string $calculatorName,
        array $records,
        array $warnings,
        array $issues,
        DateTimeImmutable $computedAt,
    ): self {
        return new self(
            $calculatorName,
            $records,
            $warnings,
            $issues,
            new AnalyticsSummary($calculatorName, count($records), count($issues), count($warnings), $computedAt),
        );
    }

    public function calculatorName(): string
    {
        return $this->calculatorName;
    }

    /** @return mixed[] */
    public function records(): array
    {
        return $this->records;
    }

    /** @return AnalyticsWarning[] */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /** @return AnalyticsIssue[] */
    public function issues(): array
    {
        return $this->issues;
    }

    public function summary(): AnalyticsSummary
    {
        return $this->summary;
    }
}
