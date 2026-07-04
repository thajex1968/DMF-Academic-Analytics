<?php

declare(strict_types=1);

namespace DMF\Analytics\Contracts;

/**
 * Combines multiple calculator results into one — e.g. rolling several
 * scope-specific results up into a single reportable result. No
 * implementation exists yet in this Sprint.
 */
interface AnalyticsAggregatorInterface
{
    /**
     * @param AnalyticsResultInterface[] $results
     */
    public function aggregate(array $results): AnalyticsResultInterface;
}
