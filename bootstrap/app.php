<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocale;
use App\Support\Problem\ProblemDetailsRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api/v1',
        then: function (): void {
            Route::group([], __DIR__.'/../routes/health.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append([SetLocale::class]);

        // Unauthenticated requests get a 401 problem response, never a redirect.
        $middleware->redirectGuestsTo(fn (): ?string => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(
            fn (Throwable $exception, Request $request) => app(ProblemDetailsRenderer::class)->render($exception),
        );
    })->create();
