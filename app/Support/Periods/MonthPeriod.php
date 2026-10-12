<?php

declare(strict_types=1);

namespace App\Support\Periods;

use Carbon\CarbonImmutable;

final readonly class MonthPeriod implements PeriodStrategy
{
    public function start(CarbonImmutable $day): CarbonImmutable
    {
        return $day->startOfMonth()->startOfDay();
    }

    public function next(CarbonImmutable $start): CarbonImmutable
    {
        return $start->addMonthNoOverflow();
    }
}
