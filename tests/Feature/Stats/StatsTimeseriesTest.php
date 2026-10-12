<?php

declare(strict_types=1);

use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\StatsDataset;

function seriesUrl(array $query = []): string
{
    return '/api/v1/stats/timeseries?'.http_build_query([...['from' => '2026-10-01', 'to' => '2026-10-31'], ...$query]);
}

beforeEach(function (): void {
    $this->data = StatsDataset::seed();
    Sanctum::actingAs($this->data->alice);
});

it('buckets by day by default, with empty days included', function (): void {
    $data = $this->getJson(seriesUrl())->assertOk()->assertValidResponse(200)->json('data');

    $totals = collect($data)->mapWithKeys(fn (array $bucket): array => [$bucket['period_start'] => [$bucket['total_cents'], $bucket['count']]]);

    expect($data)->toHaveCount(31)
        ->and($data[0]['period_start'])->toBe('2026-10-01')
        ->and($data[30]['period_start'])->toBe('2026-10-31')
        ->and($totals['2026-10-01'])->toBe([1000, 1])
        ->and($totals['2026-10-05'])->toBe([6500, 2])
        ->and($totals['2026-10-12'])->toBe([1500, 1])
        ->and($totals['2026-10-14'])->toBe([3000, 1])
        ->and($totals['2026-10-31'])->toBe([500, 1])
        ->and($totals['2026-10-02'])->toBe([0, 0])
        ->and($totals->sum(fn (array $pair): int => $pair[0]))->toBe(12500);
});

it('buckets by ISO week starting on Monday, the first bucket starting before the range', function (): void {
    $this->getJson(seriesUrl(['granularity' => 'week']))
        ->assertOk()
        ->assertValidResponse(200)
        ->assertExactJson(['data' => [
            ['period_start' => '2026-09-28', 'total_cents' => 1000, 'count' => 1],
            ['period_start' => '2026-10-05', 'total_cents' => 6500, 'count' => 2],
            ['period_start' => '2026-10-12', 'total_cents' => 4500, 'count' => 2],
            ['period_start' => '2026-10-19', 'total_cents' => 0, 'count' => 0],
            ['period_start' => '2026-10-26', 'total_cents' => 500, 'count' => 1],
        ]]);
});

it('buckets by month and keeps empty months', function (): void {
    $this->getJson(seriesUrl(['granularity' => 'month', 'from' => '2026-08-15', 'to' => '2026-11-10']))
        ->assertOk()
        ->assertValidResponse(200)
        ->assertExactJson(['data' => [
            ['period_start' => '2026-08-01', 'total_cents' => 8000, 'count' => 2],
            ['period_start' => '2026-09-01', 'total_cents' => 5000, 'count' => 1],
            ['period_start' => '2026-10-01', 'total_cents' => 12500, 'count' => 6],
            ['period_start' => '2026-11-01', 'total_cents' => 7000, 'count' => 1],
        ]]);
});

it('is continuous and all zero when nothing was spent', function (): void {
    $data = $this->getJson(seriesUrl(['from' => '2020-02-26', 'to' => '2020-03-02']))
        ->assertOk()
        ->assertValidResponse(200)
        ->json('data');

    expect(collect($data)->pluck('period_start')->all())->toBe(['2020-02-26', '2020-02-27', '2020-02-28', '2020-02-29', '2020-03-01', '2020-03-02'])
        ->and(collect($data)->sum('total_cents'))->toBe(0);
});

it('only buckets the requested currency', function (): void {
    $data = $this->getJson(seriesUrl(['currency' => 'USD']))->assertOk()->assertValidResponse(200)->json('data');

    expect(collect($data)->sum('total_cents'))->toBe(9999)
        ->and(collect($data)->firstWhere('period_start', '2026-10-10')['total_cents'])->toBe(9999);
});

it('never buckets another owner\'s expenses', function (): void {
    Sanctum::actingAs($this->data->bob);

    $data = $this->getJson(seriesUrl(['granularity' => 'month']))->assertOk()->assertValidResponse(200)->json('data');

    expect($data)->toBe([['period_start' => '2026-10-01', 'total_cents' => 88888, 'count' => 1]]);
});

it('rejects an invalid query', function (array $query, string $field): void {
    $this->getJson('/api/v1/stats/timeseries?'.http_build_query($query))
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonValidationErrors($field);
})->with([
    'no range' => [[], 'from'],
    'unknown granularity' => [['from' => '2026-10-01', 'to' => '2026-10-31', 'granularity' => 'year'], 'granularity'],
    'custom is not a granularity' => [['from' => '2026-10-01', 'to' => '2026-10-31', 'granularity' => 'custom'], 'granularity'],
    'to before from' => [['from' => '2026-10-31', 'to' => '2026-10-01'], 'to'],
    'bad currency' => [['from' => '2026-10-01', 'to' => '2026-10-31', 'currency' => 'E'], 'currency'],
    'span beyond five years' => [['from' => '2020-01-01', 'to' => '2026-10-31'], 'to'],
]);
