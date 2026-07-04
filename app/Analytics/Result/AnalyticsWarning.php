<?php

declare(strict_types=1);

namespace DMF\Analytics\Result;

/** A non-fatal condition a calculator encountered — never blocks the pipeline. */
final class AnalyticsWarning
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $message,
    ) {
    }
}
