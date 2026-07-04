<?php

declare(strict_types=1);

namespace DMF\AI\DTO;

/**
 * `AIRecommendation.priority`'s closed vocabulary — a small, deliberate
 * addition beyond the literal CREATE list (which named `priority` as a
 * plain field, not an enum), matching this codebase's established pattern
 * for closed-vocabulary DTO fields (e.g. `DashboardAlertLevel`,
 * `CalculatorPriority`) rather than an unchecked free-text string.
 */
enum AIRecommendationPriority: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
}
