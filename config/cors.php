<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing
    |--------------------------------------------------------------------------
    | Bearer tokens travel in headers, not cookies, so credentials are not
    | sent cross-origin. Origins are an explicit allowlist from the environment.
    | ETag and X-Request-Id are exposed so the SPA can revalidate cached stats.
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'Accept-Language', 'If-None-Match', 'X-Requested-With'],

    'exposed_headers' => ['ETag', 'X-Request-Id', 'Retry-After'],

    'max_age' => 600,

    'supports_credentials' => false,
];
