<?php

declare(strict_types=1);

use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\StatsDataset;

beforeEach(function (): void {
    $this->data = StatsDataset::seed();
    Sanctum::actingAs($this->data->alice);
});

$endpoints = ['summary', 'by-category', 'timeseries'];

it('sends a strong sha256 ETag computed from the body', function (string $endpoint): void {
    $response = $this->getJson("/api/v1/stats/{$endpoint}?from=2026-10-01&to=2026-10-31")
        ->assertOk()
        ->assertValidResponse(200);

    expect($response->headers->get('ETag'))->toBe('"'.hash('sha256', (string) $response->getContent()).'"');
})->with($endpoints);

it('answers 304 without a body when If-None-Match matches', function (string $endpoint): void {
    $url = "/api/v1/stats/{$endpoint}?from=2026-10-01&to=2026-10-31";
    $etag = (string) $this->getJson($url)->headers->get('ETag');

    $this->getJson($url, ['If-None-Match' => $etag])
        ->assertStatus(304)
        ->assertValidResponse(304)
        ->assertHeader('ETag', $etag)
        ->assertNoContent(304);
})->with($endpoints);

it('matches any validator of a list, weak ones and the wildcard', function (string $header): void {
    $url = '/api/v1/stats/summary?from=2026-10-01&to=2026-10-31';
    $etag = (string) $this->getJson($url)->headers->get('ETag');
    $header = str_replace('{etag}', $etag, $header);

    $this->getJson($url, ['If-None-Match' => $header])->assertStatus(304);
})->with([
    'list' => ['"other", {etag}'],
    'weak' => ['W/{etag}'],
    'wildcard' => ['*'],
]);

it('answers 200 with the current ETag when the validator does not match', function (): void {
    $url = '/api/v1/stats/summary?from=2026-10-01&to=2026-10-31';
    $etag = (string) $this->getJson($url)->headers->get('ETag');

    $this->getJson($url, ['If-None-Match' => '"'.str_repeat('0', 64).'"'])
        ->assertOk()
        ->assertValidResponse(200)
        ->assertHeader('ETag', $etag);
});

it('serves a 304 straight from the cache without touching the database', function (): void {
    $url = '/api/v1/stats/summary?from=2026-10-01&to=2026-10-31';
    $etag = (string) $this->getJson($url)->headers->get('ETag');

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson($url, ['If-None-Match' => $etag])->assertStatus(304);

    expect(DB::getQueryLog())->toBe([]);
});

it('gives a different ETag once the data changed, so the old one no longer matches', function (): void {
    $url = '/api/v1/stats/summary?from=2026-10-01&to=2026-10-31';
    $old = (string) $this->getJson($url)->headers->get('ETag');

    Expense::factory()->ownedBy($this->data->alice)->inCategory($this->data->food)->on('2026-10-02')->create(['amount_cents' => 100]);

    $fresh = $this->getJson($url, ['If-None-Match' => $old])->assertOk()->assertValidResponse(200);

    expect($fresh->headers->get('ETag'))->not->toBe($old);
});

it('gives owners with different data different ETags', function (): void {
    $url = '/api/v1/stats/summary?from=2026-10-01&to=2026-10-31';
    $alice = (string) $this->getJson($url)->headers->get('ETag');

    Sanctum::actingAs($this->data->bob);

    $this->getJson($url, ['If-None-Match' => $alice])->assertOk();
});

it('sends no ETag on errors', function (): void {
    $this->getJson('/api/v1/stats/summary?from=2026-10-31&to=2026-10-01')
        ->assertUnprocessable()
        ->assertHeaderMissing('ETag');
});
