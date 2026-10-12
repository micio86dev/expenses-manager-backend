<?php

declare(strict_types=1);

use App\Models\Expense;
use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\StatsDataset;

const TOP_URL = '/api/v1/stats/top-expenses?from=2026-10-01&to=2026-10-31';

beforeEach(function (): void {
    $this->data = StatsDataset::seed();
    Sanctum::actingAs($this->data->alice);
});

it('lists the largest expenses first in the Expense shape', function (): void {
    $response = $this->getJson(TOP_URL)
        ->assertOk()
        ->assertValidResponse(200);

    expect($response->json('data.*.amount_cents'))->toBe([4000, 3000, 2500, 1500, 1000])
        ->and($response->json('data.0'))->toBe([
            'id' => $this->data->biggest->id,
            'description' => $this->data->biggest->description,
            'amount_cents' => 4000,
            'currency' => 'EUR',
            'category_id' => $this->data->fun->id,
            'spent_on' => '2026-10-05',
            'created_at' => $this->data->biggest->created_at->toIso8601String(),
            'updated_at' => $this->data->biggest->updated_at->toIso8601String(),
        ]);
});

it('returns five items by default and honours the limit', function (): void {
    $this->getJson(TOP_URL)->assertOk()->assertJsonCount(5, 'data');
    $this->getJson(TOP_URL.'&limit=2')->assertOk()->assertValidResponse(200)->assertJsonCount(2, 'data');
    $this->getJson(TOP_URL.'&limit=10')->assertOk()->assertJsonCount(6, 'data');
    $this->getJson(TOP_URL.'&limit=1')->assertOk()->assertJsonCount(1, 'data');
});

it('rejects a limit outside 1..10', function (string $limit): void {
    $this->getJson(TOP_URL.'&limit='.$limit)
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonValidationErrors('limit');
})->with(['zero' => '0', 'eleven' => '11', 'negative' => '-1', 'text' => 'abc', 'decimal' => '2.5']);

it('breaks ties by date descending, then id descending', function (): void {
    $a = Expense::factory()->ownedBy($this->data->alice)->inCategory($this->data->food)->on('2026-10-20')->create(['amount_cents' => 9000]);
    $b = Expense::factory()->ownedBy($this->data->alice)->inCategory($this->data->food)->on('2026-10-25')->create(['amount_cents' => 9000]);
    $c = Expense::factory()->ownedBy($this->data->alice)->inCategory($this->data->food)->on('2026-10-25')->create(['amount_cents' => 9000]);

    $sameDay = collect([$b, $c])->sortByDesc('id')->pluck('id')->all();

    $this->getJson(TOP_URL.'&limit=3')
        ->assertOk()
        ->assertValidResponse(200)
        ->assertJsonPath('data.0.id', $sameDay[0])
        ->assertJsonPath('data.1.id', $sameDay[1])
        ->assertJsonPath('data.2.id', $a->id);
});

it('only lists expenses of the requested currency', function (): void {
    $this->getJson(TOP_URL.'&currency=USD')
        ->assertOk()
        ->assertValidResponse(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.amount_cents', 9999)
        ->assertJsonPath('data.0.currency', 'USD');

    expect($this->getJson(TOP_URL)->json('data.*.currency'))->each->toBe('EUR');
});

it('never lists another owner\'s expenses', function (): void {
    Sanctum::actingAs($this->data->bob);

    $this->getJson(TOP_URL)
        ->assertOk()
        ->assertValidResponse(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.amount_cents', 88888);
});

it('is empty for a range without spending', function (): void {
    $this->getJson('/api/v1/stats/top-expenses?from=2020-01-01&to=2020-01-31')
        ->assertOk()
        ->assertValidResponse(200)
        ->assertExactJson(['data' => []]);
});

it('does not cache across different limits', function (): void {
    $this->getJson(TOP_URL.'&limit=2')->assertJsonCount(2, 'data');
    $this->getJson(TOP_URL.'&limit=3')->assertJsonCount(3, 'data');
});

it('rejects a missing or invalid range', function (string $query, string $field): void {
    $this->getJson('/api/v1/stats/top-expenses'.$query)
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonValidationErrors($field);
})->with([
    'no range' => ['', 'from'],
    'missing to' => ['?from=2026-10-01', 'to'],
    'to before from' => ['?from=2026-10-31&to=2026-10-01', 'to'],
    'lowercase currency' => ['?from=2026-10-01&to=2026-10-31&currency=eur', 'currency'],
    'span beyond five years' => ['?from=2021-01-01&to=2026-01-01', 'to'],
]);

it('accepts exactly five years', function (): void {
    $this->getJson('/api/v1/stats/top-expenses?from=2021-01-01&to=2025-12-31')
        ->assertOk()
        ->assertValidResponse(200);
});

it('sends a strong ETag, varies on Accept-Language and answers 304 on a match', function (): void {
    $first = $this->getJson(TOP_URL)->assertOk();
    $etag = (string) $first->headers->get('ETag');

    expect($etag)->toBe('"'.hash('sha256', (string) $first->getContent()).'"')
        ->and($first->headers->get('Vary'))->toContain('Accept-Language');

    $this->getJson(TOP_URL, ['If-None-Match' => $etag])
        ->assertStatus(304)
        ->assertValidResponse(304)
        ->assertHeader('ETag', $etag)
        ->assertNoContent(304);
});
