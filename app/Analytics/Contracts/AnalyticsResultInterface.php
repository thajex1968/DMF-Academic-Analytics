<?php

declare(strict_types=1);

namespace DMF\Analytics\Contracts;

use DMF\Analytics\Result\AnalyticsIssue;
use DMF\Analytics\Result\AnalyticsSummary;
use DMF\Analytics\Result\AnalyticsWarning;

/** The outcome of one AnalyticsCalculatorInterface::calculate() run. */
interface AnalyticsResultInterface
{
    public function calculatorName(): string;

    /** @return mixed[] */
    public function records(): array;

    /** @return AnalyticsWarning[] */
    public function warnings(): array;

    /** @return AnalyticsIssue[] */
    public function issues(): array;

    public function summary(): AnalyticsSummary;
}
