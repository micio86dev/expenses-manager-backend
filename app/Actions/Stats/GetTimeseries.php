<?php

declare(strict_types=1);

namespace App\Actions\Stats;

use App\Data\Stats\StatsQuery;
use App\Data\Stats\TimeseriesBucket;
use App\Repositories\Contracts\StatsRepository;
use App\Support\DateRange;
use App\Support\Periods\Period;
use App\Support\Periods\PeriodResolver;
use InvalidArgumentException;

final readonly class GetTimeseries
{
    public function __construct(
        private StatsRepository $stats,
        private PeriodResolver $periods,
    ) {}

    /**
     * One bucket per day, ISO week or month touching the range, empty ones included so a chart is continuous.
     * Totals only cover days inside the range, so a partial first or last bucket holds just those days.
     *
     * @return list<TimeseriesBucket>
     */
    public function __invoke(StatsQuery $query, Period $granularity): array
    {
        if (! in_array($granularity, Period::granularities(), true)) {
            throw new InvalidArgumentException('A time series can only be bucketed by day, week or month.');
        }

        /** @var array<string, array{0: int, 1: int}> $sums */
        $sums = [];

        foreach ($this->stats->dailyTotals($query) as $day) {
            $key = $this->periods->bucketStart($granularity, $day->day)->format(DateRange::FORMAT);
            $sums[$key] = [($sums[$key][0] ?? 0) + $day->totalCents, ($sums[$key][1] ?? 0) + $day->count];
        }

        $buckets = [];

        foreach ($this->periods->bucketStarts($granularity, $query->range) as $start) {
            [$total, $count] = $sums[$start->format(DateRange::FORMAT)] ?? [0, 0];
            $buckets[] = new TimeseriesBucket($start, $total, $count);
        }

        return $buckets;
    }
}
