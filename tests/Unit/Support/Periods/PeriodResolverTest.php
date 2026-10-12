<?php

declare(strict_types=1);

use App\Support\DateRange;
use App\Support\Periods\Period;
use App\Support\Periods\PeriodResolver;
use Carbon\CarbonImmutable;

function day(string $date): CarbonImmutable
{
    return DateRange::day($date);
}

/**
 * @param  list<CarbonImmutable>  $days
 * @return list<string>
 */
function dates(array $days): array
{
    return array_map(static fn (CarbonImmutable $day): string => $day->format('Y-m-d'), $days);
}

it('lists the granularities a time series can use', function (): void {
    expect(Period::granularities())->toBe([Period::Day, Period::Week, Period::Month]);
});

it('resolves the day containing an anchor', function (): void {
    $range = (new PeriodResolver)->resolve(Period::Day, day('2026-10-14'));

    expect($range->from->toDateString())->toBe('2026-10-14')
        ->and($range->to->toDateString())->toBe('2026-10-14');
});

it('resolves ISO weeks from Monday to Sunday', function (string $anchor, string $from, string $to): void {
    $range = (new PeriodResolver)->resolve(Period::Week, day($anchor));

    expect($range->from->toDateString())->toBe($from)
        ->and($range->to->toDateString())->toBe($to);
})->with([
    'midweek' => ['2026-10-14', '2026-10-12', '2026-10-18'],
    'monday' => ['2026-10-12', '2026-10-12', '2026-10-18'],
    'sunday' => ['2026-10-18', '2026-10-12', '2026-10-18'],
    'week across the year end' => ['2027-01-01', '2026-12-28', '2027-01-03'],
    'monday before new year' => ['2024-12-30', '2024-12-30', '2025-01-05'],
    'new year inside the previous ISO year' => ['2025-01-01', '2024-12-30', '2025-01-05'],
]);

it('resolves calendar months including month length and leap years', function (string $anchor, string $from, string $to): void {
    $range = (new PeriodResolver)->resolve(Period::Month, day($anchor));

    expect($range->from->toDateString())->toBe($from)
        ->and($range->to->toDateString())->toBe($to);
})->with([
    '31 days' => ['2026-10-14', '2026-10-01', '2026-10-31'],
    '30 days' => ['2026-04-30', '2026-04-01', '2026-04-30'],
    'february' => ['2026-02-10', '2026-02-01', '2026-02-28'],
    'leap february' => ['2024-02-29', '2024-02-01', '2024-02-29'],
    'december' => ['2026-12-31', '2026-12-01', '2026-12-31'],
]);

it('treats a custom period as exactly the chosen range', function (): void {
    $custom = DateRange::fromStrings('2026-10-03', '2026-10-20');
    $resolver = new PeriodResolver;

    expect($resolver->resolve(Period::Custom, day('2026-10-10'), $custom))->toEqual($custom)
        ->and(dates($resolver->bucketStarts(Period::Custom, $custom)))->toBe(['2026-10-03'])
        ->and($resolver->bucketStart(Period::Custom, day('2026-10-10'), $custom)->toDateString())->toBe('2026-10-03');
});

it('refuses a custom period without a range', function (): void {
    expect(fn () => (new PeriodResolver)->resolve(Period::Custom, day('2026-10-10')))
        ->toThrow(InvalidArgumentException::class);
});

it('maps a day to the first day of its bucket', function (Period $period, string $dayValue, string $start): void {
    expect((new PeriodResolver)->bucketStart($period, day($dayValue))->toDateString())->toBe($start);
})->with([
    'day' => [Period::Day, '2026-10-14', '2026-10-14'],
    'week' => [Period::Week, '2026-10-18', '2026-10-12'],
    'month' => [Period::Month, '2026-10-31', '2026-10-01'],
]);

it('lists every bucket start of a range, partial first and last buckets included', function (Period $period, string $from, string $to, array $expected): void {
    $starts = (new PeriodResolver)->bucketStarts($period, DateRange::fromStrings($from, $to));

    expect(dates($starts))->toBe($expected);
})->with([
    'days' => [Period::Day, '2026-10-30', '2026-11-02', ['2026-10-30', '2026-10-31', '2026-11-01', '2026-11-02']],
    'single day' => [Period::Day, '2026-10-30', '2026-10-30', ['2026-10-30']],
    'weeks' => [Period::Week, '2026-10-14', '2026-11-03', ['2026-10-12', '2026-10-19', '2026-10-26', '2026-11-02']],
    'weeks across new year' => [Period::Week, '2026-12-30', '2027-01-05', ['2026-12-28', '2027-01-04']],
    'months' => [Period::Month, '2026-11-15', '2027-02-02', ['2026-11-01', '2026-12-01', '2027-01-01', '2027-02-01']],
    'leap february' => [Period::Month, '2024-02-29', '2024-03-01', ['2024-02-01', '2024-03-01']],
]);
