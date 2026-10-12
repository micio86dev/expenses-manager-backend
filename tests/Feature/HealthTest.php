<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

afterEach(function (): void {
    config(['database.default' => 'sqlite']);
});

it('reports liveness without authentication or a session', function (): void {
    $response = $this->getJson('/health');

    $response->assertOk()
        ->assertExactJson(['status' => 'ok'])
        ->assertHeaderMissing('Set-Cookie');

    assertMatchesContract($response, 'GET', '/health');
});

it('reports readiness when the database answers', function (): void {
    $response = $this->getJson('/ready');

    $response->assertOk()
        ->assertExactJson(['status' => 'ready'])
        ->assertHeaderMissing('Set-Cookie');

    assertMatchesContract($response, 'GET', '/ready');
});

it('answers 503 problem details when the database is unreachable', function (): void {
    config([
        'database.connections.unreachable' => [
            'driver' => 'sqlite',
            'database' => '/nonexistent-directory/missing.sqlite',
            'prefix' => '',
        ],
        'database.default' => 'unreachable',
    ]);
    DB::purge('unreachable');

    $response = $this->getJson('/ready');

    $response->assertStatus(503)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 503);

    expect($response->getContent())->not->toContain('nonexistent-directory');
    assertMatchesContract($response, 'GET', '/ready');
});

it('answers 503 when the schema has not been migrated', function (): void {
    config([
        'database.connections.empty' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
        'database.default' => 'empty',
    ]);

    $this->getJson('/ready')->assertStatus(503);
});

it('keeps the probes outside the versioned api prefix', function (): void {
    $this->getJson('/api/v1/health')->assertNotFound();
    $this->getJson('/api/v1/ready')->assertNotFound();
});
