<?php

declare(strict_types=1);

use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\StatsDataset;

const BY_CATEGORY_URL = '/api/v1/stats/by-category?from=2026-10-01&to=2026-10-31';

beforeEach(function (): void {
    $this->data = StatsDataset::seed();
    Sanctum::actingAs($this->data->alice);
});

it('ranks categories by total with shares computed from integer totals', function (): void {
    $this->getJson(BY_CATEGORY_URL)
        ->assertOk()
        ->assertValidResponse(200)
        ->assertExactJson(['data' => [
            ['category_id' => $this->data->food->id, 'name' => 'Food', 'total_cents' => 6500, 'count' => 3, 'share_percent' => 52.0],
            ['category_id' => $this->data->fun->id, 'name' => 'Fun', 'total_cents' => 4500, 'count' => 2, 'share_percent' => 36.0],
            ['category_id' => $this->data->transport->id, 'name' => 'Transport', 'total_cents' => 1500, 'count' => 1, 'share_percent' => 12.0],
        ]]);
});

it('returns an empty list when nothing was spent', function (): void {
    $this->getJson('/api/v1/stats/by-category?from=2020-01-01&to=2020-01-31')
        ->assertOk()
        ->assertValidResponse(200)
        ->assertExactJson(['data' => []]);
});

it('only lists the requested currency', function (): void {
    $this->getJson(BY_CATEGORY_URL.'&currency=USD')
        ->assertOk()
        ->assertValidResponse(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.total_cents', 9999);
});

it('never lists another owner\'s categories or totals', function (): void {
    Sanctum::actingAs($this->data->bob);

    $this->getJson(BY_CATEGORY_URL)
        ->assertOk()
        ->assertValidResponse(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Bobs')
        ->assertJsonPath('data.0.total_cents', 88888);
});

it('rejects an invalid range or currency', function (string $query, string $field): void {
    $this->getJson('/api/v1/stats/by-category'.$query)
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonValidationErrors($field);
})->with([
    'no range' => ['', 'from'],
    'to before from' => ['?from=2026-10-31&to=2026-10-01', 'to'],
    'bad currency' => ['?from=2026-10-01&to=2026-10-31&currency=euro', 'currency'],
    'span beyond five years' => ['?from=2020-01-01&to=2026-10-31', 'to'],
]);
