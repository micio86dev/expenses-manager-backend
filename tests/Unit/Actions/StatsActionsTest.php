<?php

declare(strict_types=1);

use App\Actions\Stats\GetCategoryStats;
use App\Actions\Stats\GetStatsSummary;
use App\Actions\Stats\GetTimeseries;
use App\Data\Stats\StatsQuery;
use App\Support\DateRange;
use App\Support\Periods\Period;
use App\Support\Periods\PeriodResolver;
use Tests\Fakes\InMemoryStatsRepository;

function statsQuery(string $from, string $to, string $currency = 'EUR'): StatsQuery
{
    return new StatsQuery(DateRange::fromStrings($from, $to), $currency);
}

describe('GetCategoryStats', function (): void {
    it('ranks categories by total, then name, with shares of the integer total', function (): void {
        $repository = (new InMemoryStatsRepository)
            ->spend('2026-10-01', 100, 'Zoo')
            ->spend('2026-10-02', 100, 'Art')
            ->spend('2026-10-03', 800, 'Food')
            ->spend('2026-10-04', 5000, 'Food', 'USD');

        $stats = (new GetCategoryStats($repository))(statsQuery('2026-10-01', '2026-10-31'));

        expect(array_map(fn ($stat): string => $stat->name, $stats))->toBe(['Food', 'Art', 'Zoo'])
            ->and(array_map(fn ($stat): float => $stat->sharePercent, $stats))->toBe([80.0, 10.0, 10.0])
            ->and($stats[0]->totalCents)->toBe(800)
            ->and($stats[0]->count)->toBe(1);
    });

    it('is empty without spending', function (): void {
        expect((new GetCategoryStats(new InMemoryStatsRepository))(statsQuery('2026-10-01', '2026-10-31')))->toBe([]);
    });
});

describe('GetStatsSummary', function (): void {
    function summaryAction(InMemoryStatsRepository $repository): GetStatsSummary
    {
        return new GetStatsSummary($repository, new GetCategoryStats($repository));
    }

    it('compares with the previous period of equal length and rounds the daily average', function (): void {
        $repository = (new InMemoryStatsRepository)
            ->spend('2026-10-10', 1001, 'Food')
            ->spend('2026-10-11', 2000, 'Fun')
            ->spend('2026-10-07', 1000, 'Food')
            ->spend('2026-10-06', 9999, 'Food');

        // 2026-10-09..2026-10-11 is 3 days; the previous period is 2026-10-06..2026-10-08.
        $summary = summaryAction($repository)(statsQuery('2026-10-09', '2026-10-11'));

        expect($summary->totalCents)->toBe(3001)
            ->and($summary->count)->toBe(2)
            ->and($summary->dailyAverageCents)->toBe(1000)
            ->and($summary->previousTotalCents)->toBe(10999)
            ->and($summary->deltaPercent)->toBe(-72.72)
            ->and($summary->topCategory?->name)->toBe('Fun')
            ->and($summary->biggestExpense?->amount_cents)->toBe(2000);
    });

    it('has no change when the previous period had no spending', function (): void {
        $repository = (new InMemoryStatsRepository)->spend('2026-10-10', 500);

        $summary = summaryAction($repository)(statsQuery('2026-10-10', '2026-10-10'));

        expect($summary->previousTotalCents)->toBe(0)
            ->and($summary->deltaPercent)->toBeNull();
    });

    it('has no top category or biggest expense when nothing was spent', function (): void {
        $summary = summaryAction(new InMemoryStatsRepository)(statsQuery('2026-10-10', '2026-10-12'));

        expect($summary->totalCents)->toBe(0)
            ->and($summary->dailyAverageCents)->toBe(0)
            ->and($summary->topCategory)->toBeNull()
            ->and($summary->biggestExpense)->toBeNull();
    });
});

describe('GetTimeseries', function (): void {
    function timeseries(InMemoryStatsRepository $repository, Period $granularity, string $from, string $to): array
    {
        return array_map(
            fn ($bucket): array => [$bucket->periodStart->toDateString(), $bucket->totalCents, $bucket->count],
            (new GetTimeseries($repository, new PeriodResolver))(statsQuery($from, $to), $granularity),
        );
    }

    it('fills empty days so the series is continuous', function (): void {
        $repository = (new InMemoryStatsRepository)->spend('2026-10-01', 100)->spend('2026-10-03', 300)->spend('2026-10-03', 50);

        expect(timeseries($repository, Period::Day, '2026-10-01', '2026-10-04'))->toBe([
            ['2026-10-01', 100, 1],
            ['2026-10-02', 0, 0],
            ['2026-10-03', 350, 2],
            ['2026-10-04', 0, 0],
        ]);
    });

    it('sums days into ISO weeks across the year end', function (): void {
        $repository = (new InMemoryStatsRepository)
            ->spend('2026-12-29', 100)
            ->spend('2027-01-03', 200)
            ->spend('2027-01-04', 400);

        expect(timeseries($repository, Period::Week, '2026-12-30', '2027-01-10'))->toBe([
            ['2026-12-28', 200, 1],
            ['2027-01-04', 400, 1],
        ]);
    });

    it('sums days into months and keeps empty months', function (): void {
        $repository = (new InMemoryStatsRepository)->spend('2024-02-29', 100)->spend('2024-04-01', 700);

        expect(timeseries($repository, Period::Month, '2024-02-10', '2024-04-30'))->toBe([
            ['2024-02-01', 100, 1],
            ['2024-03-01', 0, 0],
            ['2024-04-01', 700, 1],
        ]);
    });

    it('rejects a period that cannot bucket a time series', function (): void {
        expect(fn () => (new GetTimeseries(new InMemoryStatsRepository, new PeriodResolver))(statsQuery('2026-10-01', '2026-10-02'), Period::Custom))
            ->toThrow(InvalidArgumentException::class);
    });
});
