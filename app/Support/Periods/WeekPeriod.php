<?php

declare(strict_types=1);

namespace App\Support\Periods;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * ISO weeks: Monday to Sunday, independent of the application locale.
 */
final readonly class WeekPeriod implements PeriodStrategy
{
    public function start(CarbonImmutable $day): CarbonImmutable
    {
        return $day->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
    }

    public function next(CarbonImmutable $start): CarbonImmutable
    {
        return $start->addWeek();
    }
}
