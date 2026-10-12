<?php

declare(strict_types=1);

use App\Data\Stats\StatsQuery;
use App\Support\DateRange;

it('defaults to euro', function (): void {
    expect((new StatsQuery(DateRange::fromStrings('2026-10-01', '2026-10-31')))->currency)->toBe('EUR');
});

it('rejects anything but an uppercase ISO 4217 shaped currency', function (string $currency): void {
    expect(fn () => new StatsQuery(DateRange::fromStrings('2026-10-01', '2026-10-31'), $currency))
        ->toThrow(InvalidArgumentException::class);
})->with(['', 'eur', 'EURO', '978', 'E1R']);

it('protects the database from ranges longer than five years', function (): void {
    expect(fn () => new StatsQuery(DateRange::fromStrings('2021-01-01', '2026-01-01')))
        ->toThrow(InvalidArgumentException::class)
        ->and(new StatsQuery(DateRange::fromStrings('2021-01-01', '2025-12-31')))->toBeInstanceOf(StatsQuery::class);
});

it('derives the previous period with the same currency', function (): void {
    $previous = (new StatsQuery(DateRange::fromStrings('2026-10-08', '2026-10-14'), 'USD'))->previous();

    expect($previous->range->from->toDateString())->toBe('2026-10-01')
        ->and($previous->range->to->toDateString())->toBe('2026-10-07')
        ->and($previous->currency)->toBe('USD');
});
