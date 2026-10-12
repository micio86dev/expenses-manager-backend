<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Support\Auth\EmailKey;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Limits are read per request so they can be tuned (and tested) through config/throttle.php.
        // Two buckets: one account from one IP, and one IP across all accounts (password spraying).
        RateLimiter::for('login', fn (Request $request): array => [
            Limit::perMinute(config()->integer('throttle.login'))
                ->by('login|'.(EmailKey::normalise($request->input('email')) ?? '-').'|'.$this->clientIp($request)),
            Limit::perMinute(config()->integer('throttle.login_ip'))
                ->by('login-ip|'.$this->clientIp($request)),
        ]);

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
