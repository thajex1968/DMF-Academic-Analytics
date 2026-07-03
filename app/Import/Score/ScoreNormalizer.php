<?php

declare(strict_types=1);

namespace DMF\Import\Score;

use InvalidArgumentException;

/**
 * Converts a raw, parsed score cell ("87.5", " 92 ") into a float suitable
 * for `student_scores.score DECIMAL(5,2)`, and enforces the real 0.00–100.00
 * range precisely.
 *
 * Deliberately does not use `Dmf\Core\Validation\Rules\IntRangeRule` for the
 * range check — verified directly that it casts its value to `(int)` before
 * comparing, so a value like "100.5" would incorrectly pass a 0–100 check
 * (truncated to 100). Reusing it here would silently accept out-of-range
 * decimal scores, so this class does its own float comparison instead.
 */
final class ScoreNormalizer
{
    private const MIN = 0.0;
    private const MAX = 100.0;

    /** @throws InvalidArgumentException If $rawScore is not a valid, in-range score. */
    public function normalize(string $rawScore): float
    {
        $trimmed = trim($rawScore);

        if ($trimmed === '' || !is_numeric($trimmed)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid numeric score.', $rawScore));
        }

        $value = round((float) $trimmed, 2);

        if ($value < self::MIN || $value > self::MAX) {
            throw new InvalidArgumentException(
                sprintf('Score %s is outside the valid 0.00–100.00 range.', number_format($value, 2)),
            );
        }

        return $value;
    }
}
