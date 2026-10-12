<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

const LIMITED_URL = '/api/v1/stats/summary?from=2026-10-01&to=2026-10-31';

it('limits statistics per user with a problem+json 429 and Retry-After', function (): void {
    config(['throttle.stats' => 2]);
    Sanctum::actingAs(User::factory()->create());

    $this->getJson(LIMITED_URL)->assertOk();
    $this->getJson(LIMITED_URL)->assertOk();

    $this->getJson(LIMITED_URL)
        ->assertStatus(429)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertHeader('Retry-After')
        ->assertJsonPath('status', 429)
        ->assertJsonStructure(['type', 'title', 'status', 'detail']);
});

it('counts 304 revalidations and every statistics endpoint against the same limit', function (): void {
    config(['throttle.stats' => 3]);
    Sanctum::actingAs(User::factory()->create());

    $etag = (string) $this->getJson(LIMITED_URL)->headers->get('ETag');
    $this->getJson(LIMITED_URL, ['If-None-Match' => $etag])->assertStatus(304);
    $this->getJson('/api/v1/stats/by-category?from=2026-10-01&to=2026-10-31')->assertOk();

    $this->getJson('/api/v1/stats/timeseries?from=2026-10-01&to=2026-10-31')->assertStatus(429);
});

it('keeps the limit separate per user', function (): void {
    config(['throttle.stats' => 1]);

    Sanctum::actingAs(User::factory()->create());
    $this->getJson(LIMITED_URL)->assertOk();
    $this->getJson(LIMITED_URL)->assertStatus(429);

    Sanctum::actingAs(User::factory()->create());
    $this->getJson(LIMITED_URL)->assertOk();
});

it('does not spend the general API budget, nor is it spent by it', function (): void {
    config(['throttle.stats' => 1, 'throttle.api' => 1]);
    Sanctum::actingAs(User::factory()->create());

    $this->getJson(LIMITED_URL)->assertOk();
    $this->getJson('/api/v1/categories')->assertOk();

    $this->getJson(LIMITED_URL)->assertStatus(429);
    $this->getJson('/api/v1/categories')->assertStatus(429);
});

it('defaults to 120 requests per minute', function (): void {
    expect(config('throttle.stats'))->toBe(120);
});

it('counts the top expenses endpoint against the statistics limit', function (): void {
    config(['throttle.stats' => 1]);
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/stats/top-expenses?from=2026-10-01&to=2026-10-31')->assertOk();
    $this->getJson('/api/v1/stats/top-expenses?from=2026-10-01&to=2026-10-31')->assertStatus(429);
});
