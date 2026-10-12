<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

it('allows configured origins to read the etag and request id headers', function (): void {
    $response = $this->withHeaders(['Origin' => 'http://localhost:5173'])
        ->getJson('/api/v1/categories');

    $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    expect((string) $response->headers->get('Access-Control-Expose-Headers'))
        ->toContain('ETag')
        ->toContain('X-Request-Id');
});

it('does not echo unknown origins', function (): void {
    $response = $this->withHeaders(['Origin' => 'https://evil.example'])
        ->getJson('/api/v1/categories');

    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://evil.example');
});

it('reads allowed origins from the environment', function (): void {
    expect(Config::get('cors.allowed_origins'))->toBeArray()->toContain('http://localhost:5173');
});
