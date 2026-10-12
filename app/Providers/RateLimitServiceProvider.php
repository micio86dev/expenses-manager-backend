<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

final class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Limits are read per request so they can be tuned (and tested) through config/throttle.php.
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(config()->integer('throttle.login'))
            ->by(Str::lower($request->string('email')->toString()).'|'.$this->clientIp($request)));

        RateLimiter::for('register', fn (Request $request): Limit => Limit::perMinute(config()->integer('throttle.register'))
            ->by($this->clientIp($request)));

        RateLimiter::for('api', function (Request $request): Limit {
            $user = $request->user('sanctum');

            return Limit::perMinute(config()->integer('throttle.api'))
                ->by($user instanceof User ? $user->id : $this->clientIp($request));
        });

        RateLimiter::for('stats', function (Request $request): Limit {
            $user = $request->user('sanctum');

            return Limit::perMinute(config()->integer('throttle.stats'))
                ->by($user instanceof User ? $user->id : $this->clientIp($request));
        });
    }

    private function clientIp(Request $request): string
    {
        return $request->ip() ?? 'unknown';
    }
}
