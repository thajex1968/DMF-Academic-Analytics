<?php

declare(strict_types=1);

namespace DMF\Analytics\Result;

/** A specific problem tied to one Canonical record a calculator processed. */
final class AnalyticsIssue
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $message,
    ) {
    }
}
