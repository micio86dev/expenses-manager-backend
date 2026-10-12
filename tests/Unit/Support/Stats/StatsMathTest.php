<?php

declare(strict_types=1);

use App\Support\Stats\StatsMath;

it('averages per day rounding half up on integers', function (int $total, int $days, int $expected): void {
    expect(StatsMath::dailyAverage($total, $days))->toBe($expected);
})->with([
    'exact' => [3000, 3, 1000],
    'rounds down' => [1000, 3, 333],
    'rounds up' => [1001, 3, 334],
    'half rounds up' => [5, 2, 3],
    'nothing spent' => [0, 31, 0],
    'single day' => [1234, 1, 1234],
    'total near the integer limit' => [PHP_INT_MAX, 1, PHP_INT_MAX],
    'huge total rounds half up' => [PHP_INT_MAX - 1, 2, 4_611_686_018_427_387_903],
]);

it('refuses to average over less than one day', function (): void {
    expect(fn () => StatsMath::dailyAverage(100, 0))->toThrow(InvalidArgumentException::class);
});

it('computes the change against the previous period', function (int $current, int $previous, ?float $expected): void {
    expect(StatsMath::deltaPercent($current, $previous))->toBe($expected);
})->with([
    'increase' => [150, 100, 50.0],
    'decrease' => [50, 100, -50.0],
    'unchanged' => [100, 100, 0.0],
    'doubled' => [200, 100, 100.0],
    'fraction rounds to two decimals' => [1, 3, -66.67],
    'spent nothing now' => [0, 100, -100.0],
    'no previous spending' => [100, 0, null],
    'nothing at all' => [0, 0, null],
]);

it('computes a share from integer totals', function (int $part, int $total, float $expected): void {
    expect(StatsMath::sharePercent($part, $total))->toBe($expected);
})->with([
    'half' => [50, 100, 50.0],
    'whole' => [100, 100, 100.0],
    'third' => [1, 3, 33.33],
    'two thirds' => [2, 3, 66.67],
    'empty total' => [0, 0, 0.0],
]);
