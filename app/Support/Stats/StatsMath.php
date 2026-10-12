<?php

declare(strict_types=1);

namespace App\Support\Stats;

use InvalidArgumentException;

/**
 * The arithmetic behind the statistics, kept apart so its rounding rules live in one place.
 * Money stays in integer minor units; percentages are rounded to two decimals.
 */
final class StatsMath
{
    public const int PERCENT_PRECISION = 2;

    /**
     * Average per day in minor units, rounded half up using integers only.
     */
    public static function dailyAverage(int $totalCents, int $days): int
    {
        if ($days < 1) {
            throw new InvalidArgumentException('The average needs at least one day.');
        }

        return intdiv(2 * $totalCents + $days, 2 * $days);
    }

    /**
     * Change of `$current` against `$previous` in percent, or null when there is no previous spending.
     */
    public static function deltaPercent(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round((($current - $previous) * 100) / $previous, self::PERCENT_PRECISION);
    }

    /**
     * Share of `$part` in `$total` in percent, from integer totals; 0 when there is no total.
     */
    public static function sharePercent(int $part, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($part * 100) / $total, self::PERCENT_PRECISION);
    }
}
