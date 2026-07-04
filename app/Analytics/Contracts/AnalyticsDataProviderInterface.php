<?php

declare(strict_types=1);

namespace DMF\Analytics\Contracts;

use DMF\Analytics\Canonical\AnalyticsContext;

/**
 * Supplies an AnalyticsContext to whatever runs the pipeline, without the
 * caller needing to know whether it came from a live Normalization run or
 * some other source. No implementation exists yet in this Sprint —
 * AnalyticsContextFactory::fromNormalizationResult() is the one concrete
 * path built so far, and is not itself wired behind this interface, since
 * doing so was not asked for and would be speculative (YAGNI).
 */
interface AnalyticsDataProviderInterface
{
    public function provide(): AnalyticsContext;
}
