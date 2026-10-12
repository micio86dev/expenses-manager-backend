<?php

declare(strict_types=1);

namespace App\Support\Periods;

use Carbon\CarbonImmutable;

final readonly class DayPeriod implements PeriodStrategy
{
    public function start(CarbonImmutable $day): CarbonImmutable
    {
        return $day->startOfDay();
    }

    public function next(CarbonImmutable $start): CarbonImmutable
    {
        return $start->addDay();
    }
}
