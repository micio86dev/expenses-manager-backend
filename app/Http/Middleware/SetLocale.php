<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Locale\LocaleRegistry;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale: Accept-Language against the allowlist, then the
 * signed-in user's stored preference, then the configured default.
 */
final readonly class SetLocale
{
    public function __construct(
        private Application $app,
        private LocaleRegistry $locales,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->locales->fromAcceptLanguage($request)
            ?? $this->userLocale($request)
            ?? $this->locales->default();

        $this->app->setLocale($locale);

        return $next($request);
    }

    private function userLocale(Request $request): ?string
    {
        $user = $request->user('sanctum');

        if (! $user instanceof User || ! $this->locales->isSupported($user->locale)) {
            return null;
        }

        return $user->locale;
    }
}
