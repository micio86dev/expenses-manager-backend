<?php

declare(strict_types=1);

/*
 * Rate limits, in attempts per minute. Limiters are registered in
 * App\Providers\RateLimitServiceProvider.
 */
return [
    'login' => (int) env('THROTTLE_LOGIN_PER_MINUTE', 5),
    'register' => (int) env('THROTTLE_REGISTER_PER_MINUTE', 10),
    'api' => (int) env('THROTTLE_API_PER_MINUTE', 120),
    'stats' => (int) env('THROTTLE_STATS_PER_MINUTE', 120),
];
