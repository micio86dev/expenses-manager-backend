<?php

declare(strict_types=1);

namespace App\Support\Periods;

use App\Support\DateRange;
use Carbon\CarbonImmutable;

/**
 * A single period that is exactly the range the user picked.
 */
final readonly class CustomPeriod implements PeriodStrategy
{
    public function __construct(private DateRange $range) {}

    public function start(CarbonImmutable $day): CarbonImmutable
    {
        return $this->range->from;
    }

    public function next(CarbonImmutable $start): CarbonImmutable
    {
        return $this->range->to->addDay();
    }
}
