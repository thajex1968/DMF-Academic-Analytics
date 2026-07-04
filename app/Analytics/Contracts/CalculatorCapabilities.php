<?php

declare(strict_types=1);

namespace DMF\Analytics\Contracts;

/**
 * What a calculator declares about itself — which Assessment Data Levels
 * (RFC-004) it can produce a meaningful result from. A calculator declares
 * this about its own logic only; it never inspects which Level actually
 * produced the AnalyticsContext it is given.
 */
final class CalculatorCapabilities
{
    public function __construct(
        public readonly bool $supportsLevel1,
        public readonly bool $supportsLevel2,
    ) {
    }
}
