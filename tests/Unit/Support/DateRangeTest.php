<?php

declare(strict_types=1);

use App\Support\DateRange;
use Carbon\CarbonImmutable;

it('holds an inclusive range of days', function (): void {
    $range = DateRange::fromStrings('2026-10-01', '2026-10-31');

    expect($range->from->toDateString())->toBe('2026-10-01')
        ->and($range->to->toDateString())->toBe('2026-10-31');
});

it('accepts a single day', function (): void {
    $range = DateRange::fromStrings('2026-10-12', '2026-10-12');

    expect($range->contains(CarbonImmutable::parse('2026-10-12')))->toBeTrue();
});

it('rejects a range that ends before it starts', function (): void {
    expect(fn () => DateRange::fromStrings('2026-10-02', '2026-10-01'))->toThrow(InvalidArgumentException::class);
});

it('rejects malformed or impossible dates', function (string $from): void {
    expect(fn () => DateRange::fromStrings($from, '2026-12-31'))->toThrow(InvalidArgumentException::class);
})->with(['', 'yesterday', '2026-02-30', '12/10/2026', '2026-1-1']);

it('knows which days it contains', function (): void {
    $range = DateRange::fromStrings('2026-10-01', '2026-10-31');

    expect($range->contains(CarbonImmutable::parse('2026-09-30')))->toBeFalse()
        ->and($range->contains(CarbonImmutable::parse('2026-10-01')))->toBeTrue()
        ->and($range->contains(CarbonImmutable::parse('2026-10-31 23:59:59')))->toBeTrue()
        ->and($range->contains(CarbonImmutable::parse('2026-11-01')))->toBeFalse();
});

it('is immutable', function (): void {
    $range = DateRange::fromStrings('2026-10-01', '2026-10-31');

    expect(fn () => $range->from = CarbonImmutable::now())->toThrow(Error::class);
});

it('counts its inclusive days', function (string $from, string $to, int $days): void {
    expect(DateRange::fromStrings($from, $to)->days())->toBe($days);
})->with([
    'single day' => ['2026-10-12', '2026-10-12', 1],
    'a month' => ['2026-10-01', '2026-10-31', 31],
    'leap february' => ['2024-02-01', '2024-02-29', 29],
    'across the year end' => ['2026-12-30', '2027-01-02', 4],
]);

it('derives the previous period of equal length ending the day before it starts', function (string $from, string $to, string $prevFrom, string $prevTo): void {
    $previous = DateRange::fromStrings($from, $to)->previous();

    expect($previous->from->toDateString())->toBe($prevFrom)
        ->and($previous->to->toDateString())->toBe($prevTo);
})->with([
    'single day' => ['2026-10-12', '2026-10-12', '2026-10-11', '2026-10-11'],
    'a week' => ['2026-10-12', '2026-10-18', '2026-10-05', '2026-10-11'],
    'a month does not become the calendar month before' => ['2026-10-01', '2026-10-31', '2026-08-31', '2026-09-30'],
    'across the year end' => ['2027-01-01', '2027-01-03', '2026-12-29', '2026-12-31'],
]);

it('knows when it spans more than a number of years', function (string $from, string $to, bool $exceeds): void {
    expect(DateRange::fromStrings($from, $to)->exceedsYears(5))->toBe($exceeds);
})->with([
    'exactly five years' => ['2021-01-01', '2025-12-31', false],
    'one day more' => ['2021-01-01', '2026-01-01', true],
    'short' => ['2026-10-01', '2026-10-31', false],
    'leap day start' => ['2024-02-29', '2029-02-27', false],
    'leap day start plus one' => ['2024-02-29', '2029-02-28', true],
]);
