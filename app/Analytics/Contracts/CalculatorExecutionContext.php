<?php

declare(strict_types=1);

namespace DMF\Analytics\Contracts;

use DateTimeImmutable;
use DMF\Analytics\Canonical\AnalyticsContext;

/**
 * Wraps the Canonical AnalyticsContext with per-run execution metadata — a
 * single `executedAt` shared by every calculator in one AnalyticsPipeline
 * run, so their AnalyticsSummary timestamps agree with each other instead
 * of each calculator stamping its own clock read.
 */
final class CalculatorExecutionContext
{
    public function __construct(
        public readonly AnalyticsContext $context,
        public readonly DateTimeImmutable $executedAt,
    ) {
    }
}
