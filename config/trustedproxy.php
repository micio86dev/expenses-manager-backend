<?php

declare(strict_types=1);

$proxies = env('TRUSTED_PROXIES');

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated IPs or CIDR ranges of the load balancers or reverse proxies
    | in front of the app, for example "10.0.0.0/8,172.16.0.0/12". Only requests
    | coming from these addresses may set X-Forwarded-* headers, which is what
    | makes request->ip() (and so every IP-based rate limit) the real client.
    | Empty trusts nothing, which is the safe default; avoid "*" in production.
    | Read by Illuminate\Http\Middleware\TrustProxies.
    |
    */

    'proxies' => is_string($proxies) && trim($proxies) !== '' ? trim($proxies) : null,

];
