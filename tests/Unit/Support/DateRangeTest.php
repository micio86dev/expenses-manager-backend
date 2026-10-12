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
