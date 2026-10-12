<?php

declare(strict_types=1);

namespace App\Support\Periods;

use App\Support\DateRange;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * The one place that knows how days, ISO weeks, months and custom ranges are laid out.
 * Everything that buckets or navigates periods goes through it.
 */
final readonly class PeriodResolver
{
    /**
     * The whole period that contains `$anchor` (a `Custom` period is the given range).
     */
    public function resolve(Period $period, CarbonImmutable $anchor, ?DateRange $custom = null): DateRange
    {
        $strategy = $this->strategy($period, $custom);
        $start = $strategy->start($anchor);

        return new DateRange($start, $strategy->next($start)->subDay());
    }

    /**
     * First day of the bucket that contains `$day`.
     */
    public function bucketStart(Period $period, CarbonImmutable $day, ?DateRange $custom = null): CarbonImmutable
    {
        return $this->strategy($period, $custom)->start($day);
    }

    /**
     * The first day of every bucket that touches the range, in order. The first bucket may
     * start before the range (a week or month that began earlier), never after its end.
     *
     * @return list<CarbonImmutable>
     */
    public function bucketStarts(Period $period, DateRange $range): array
    {
        $strategy = $this->strategy($period, $range);
        $starts = [];

        for ($start = $strategy->start($range->from); $start->lessThanOrEqualTo($range->to); $start = $strategy->next($start)) {
            $starts[] = $start;
        }

        return $starts;
    }

    private function strategy(Period $period, ?DateRange $custom): PeriodStrategy
    {
        return match ($period) {
            Period::Day => new DayPeriod,
            Period::Week => new WeekPeriod,
            Period::Month => new MonthPeriod,
            Period::Custom => new CustomPeriod($custom ?? throw new InvalidArgumentException('A custom period needs a range.')),
        };
    }
}
