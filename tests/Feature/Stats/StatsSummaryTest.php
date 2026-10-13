<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Expense;
use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\StatsDataset;

const SUMMARY_URL = '/api/v1/stats/summary?from=2026-10-01&to=2026-10-31';

beforeEach(function (): void {
    $this->data = StatsDataset::seed();
    Sanctum::actingAs($this->data->alice);
});

it('summarises the range with exact figures', function (): void {
    $this->getJson(SUMMARY_URL)
        ->assertOk()
        ->assertValidResponse(200)
        ->assertExactJson(['data' => [
            'currency' => 'EUR',
            'total_cents' => 12500,
            'count' => 6,
            'daily_average_cents' => 403,
            'previous_period' => ['total_cents' => 10000, 'delta_percent' => 25.0],
            'top_category' => [
                'category_id' => $this->data->food->id,
                'name' => 'Food',
                'total_cents' => 6500,
                'count' => 3,
                'share_percent' => 52.0,
            ],
            'biggest_expense' => [
                'id' => $this->data->biggest->id,
                'description' => $this->data->biggest->description,
                'amount_cents' => 4000,
                'currency' => 'EUR',
                'category_id' => $this->data->fun->id,
                'spent_on' => '2026-10-05',
                'created_at' => $this->data->biggest->created_at->toIso8601String(),
                'updated_at' => $this->data->biggest->updated_at->toIso8601String(),
            ],
        ]]);
});

it('reports a negative change against a bigger previous period', function (): void {
    $this->getJson('/api/v1/stats/summary?from=2026-09-01&to=2026-09-30')
        ->assertOk()
        ->assertValidResponse(200)
        ->assertJsonPath('data.total_cents', 5000)
        ->assertJsonPath('data.daily_average_cents', 167)
        ->assertJsonPath('data.previous_period.total_cents', 8000)
        ->assertJsonPath('data.previous_period.delta_percent', -37.5);
});

it('returns zeros and nulls for a range without spending', function (): void {
    $this->getJson('/api/v1/stats/summary?from=2020-01-01&to=2020-01-31')
        ->assertOk()
        ->assertValidResponse(200)
        ->assertExactJson(['data' => [
            'currency' => 'EUR',
            'total_cents' => 0,
            'count' => 0,
            'daily_average_cents' => 0,
            'previous_period' => ['total_cents' => 0, 'delta_percent' => null],
            'top_category' => null,
            'biggest_expense' => null,
        ]]);
});

it('rounds the change to two decimals', function (): void {
    $this->getJson('/api/v1/stats/summary?from=2026-11-01&to=2026-11-30')
        ->assertOk()
        ->assertValidResponse(200)
        ->assertJsonPath('data.total_cents', 7000)
        ->assertJsonPath('data.previous_period.total_cents', 11500)
        ->assertJsonPath('data.previous_period.delta_percent', -39.13);
});

it('aggregates one currency at a time and never mixes them', function (): void {
    $this->getJson(SUMMARY_URL.'&currency=USD')
        ->assertOk()
        ->assertValidResponse(200)
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.total_cents', 9999)
        ->assertJsonPath('data.count', 1)
        ->assertJsonPath('data.previous_period.total_cents', 0)
        ->assertJsonPath('data.previous_period.delta_percent', null)
        ->assertJsonPath('data.top_category.share_percent', 100);
});

it('never counts another owner\'s expenses', function (): void {
    Sanctum::actingAs($this->data->bob);

    $this->getJson(SUMMARY_URL)
        ->assertOk()
        ->assertValidResponse(200)
        ->assertJsonPath('data.total_cents', 88888)
        ->assertJsonPath('data.count', 1)
        ->assertJsonPath('data.previous_period.total_cents', 77777)
        ->assertJsonPath('data.top_category.name', 'Bobs');
});

it('names a shared system category in the request locale', function (): void {
    $system = Category::factory()->system('transport')->create();
    Expense::factory()->ownedBy($this->data->alice)->inCategory($system)->on('2026-10-20')->create(['amount_cents' => 90000]);

    $this->getJson(SUMMARY_URL, ['Accept-Language' => 'it'])
        ->assertOk()
        ->assertValidResponse(200)
        ->assertJsonPath('data.top_category.name', __('categories.system.transport', [], 'it'));

    $this->getJson(SUMMARY_URL, ['Accept-Language' => 'en'])
        ->assertOk()
        ->assertValidResponse(200)
        ->assertJsonPath('data.top_category.name', __('categories.system.transport', [], 'en'));
});

it('rejects a missing or invalid query', function (string $query, string $field): void {
    $this->getJson('/api/v1/stats/summary'.$query)
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonValidationErrors($field);
})->with([
    'no range' => ['', 'from'],
    'missing to' => ['?from=2026-10-01', 'to'],
    'missing from' => ['?to=2026-10-31', 'from'],
    'malformed from' => ['?from=last-week&to=2026-10-31', 'from'],
    'impossible to' => ['?from=2026-10-01&to=2026-02-30', 'to'],
    'to before from' => ['?from=2026-10-31&to=2026-10-01', 'to'],
    'lowercase currency' => ['?from=2026-10-01&to=2026-10-31&currency=eur', 'currency'],
    'four letter currency' => ['?from=2026-10-01&to=2026-10-31&currency=EURO', 'currency'],
    'numeric currency' => ['?from=2026-10-01&to=2026-10-31&currency=978', 'currency'],
    'span beyond five years' => ['?from=2021-01-01&to=2026-01-01', 'to'],
]);

it('accepts exactly five years', function (): void {
    $this->getJson('/api/v1/stats/summary?from=2021-01-01&to=2025-12-31')
        ->assertOk()
        ->assertValidResponse(200);
});

it('explains the span limit in the request locale', function (string $locale): void {
    $this->getJson('/api/v1/stats/summary?from=2000-01-01&to=2026-10-31', ['Accept-Language' => $locale])
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonPath('errors.to.0', __('stats.errors.range_too_long', ['years' => 5], $locale));
})->with([
    'english' => ['en'],
    'italian' => ['it'],
]);

it('summarises the longest valid range without failing on its previous period', function (): void {
    $this->getJson('/api/v1/stats/summary?from=2020-02-01&to=2025-01-31')
        ->assertOk()
        ->assertValidResponse(200);
});
