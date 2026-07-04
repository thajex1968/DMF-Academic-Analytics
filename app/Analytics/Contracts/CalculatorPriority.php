<?php

declare(strict_types=1);

namespace DMF\Analytics\Contracts;

/**
 * Declares how soon a calculator should run relative to others registered
 * in the same AnalyticsPipeline — higher value runs first. Calculators
 * remain independently executable (Sprint 4 Phase 2); priority only governs
 * execution/reporting order, never a data dependency between calculators.
 */
enum CalculatorPriority: int
{
    case HIGHEST = 100;
    case HIGH = 75;
    case NORMAL = 50;
    case LOW = 25;
    case LOWEST = 0;
}
