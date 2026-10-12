<?php

declare(strict_types=1);

namespace App\Support\Periods;

use Carbon\CarbonImmutable;

/**
 * How one kind of period is laid out on the calendar. Strategies are stateless apart from
 * the range a custom period was built from; all dates are start-of-day, immutable values.
 */
interface PeriodStrategy
{
    /**
     * First day of the period that contains the given day.
     */
    public function start(CarbonImmutable $day): CarbonImmutable;

    /**
     * First day of the period that follows the one starting at `$start`.
     */
    public function next(CarbonImmutable $start): CarbonImmutable;
}
