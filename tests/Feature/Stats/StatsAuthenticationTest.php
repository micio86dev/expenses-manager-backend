<?php

declare(strict_types=1);

it('requires authentication on every stats endpoint', function (string $path): void {
    $this->getJson("/api/v1/stats/{$path}?from=2026-10-01&to=2026-10-31")
        ->assertUnauthorized()
        ->assertValidResponse(401);
})->with(['summary', 'by-category', 'timeseries']);
