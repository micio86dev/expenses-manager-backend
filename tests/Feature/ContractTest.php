<?php

declare(strict_types=1);

use cebe\openapi\spec\Operation;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Spectator\Exceptions\ResponseValidationException;
use Spectator\Spectator;
use Spectator\Validation\ResponseValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

function contractOperation(string $path, string $method): Operation
{
    return Spectator::resolve()->paths[$path]->{strtolower($method)};
}

/**
 * @param  array<string, mixed>  $body
 */
function problemJson(array $body, int $status): Response
{
    return new JsonResponse($body, $status, ['Content-Type' => 'application/problem+json']);
}

it('loads the OpenAPI 3.1 contract', function (): void {
    expect(Spectator::resolve()->openapi)->toStartWith('3.1');
});

it('implements every operation of the contract that has shipped', function (): void {
    $routes = collect(RouteFacade::getRoutes()->getRoutes())
        ->map(fn (Route $route): array => collect($route->methods())->reject(fn (string $m): bool => $m === 'HEAD')
            ->map(fn (string $method): string => $method.' /'.Str::after($route->uri(), 'api/v1/'))
            ->all())
        ->flatten()
        ->all();

    $implemented = [
        '/auth/register', '/auth/login', '/auth/logout', '/auth/me', '/health', '/ready',
        '/categories', '/categories/{category}', '/categories/{category}/archive', '/categories/{category}/unarchive',
        '/stats/summary', '/stats/by-category', '/stats/timeseries',
    ];
    $contractOperations = collect(Spectator::resolve()->paths)
        ->flatMap(fn ($item, string $path): array => collect($item->getOperations())
            ->keys()
            ->map(fn (string $method): string => strtoupper($method).' '.$path)
            ->all())
        ->filter(fn (string $operation): bool => in_array(Str::after($operation, ' '), $implemented, true));

    expect($contractOperations)->not->toBeEmpty();

    foreach ($contractOperations as $operation) {
        expect($routes)->toContain($operation);
    }
});

it('accepts a response that satisfies the contract', function (): void {
    $user = [
        'id' => '0199c0a6-5b3b-7000-8000-000000000001',
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'locale' => 'it',
        'created_at' => '2026-10-12T10:00:00+00:00',
    ];

    ResponseValidator::validate(new JsonResponse(['data' => $user]), contractOperation('/auth/me', 'GET'), '3.1.0');

    expect(true)->toBeTrue();
});

it('rejects responses that violate the contract', function (Closure $response, string $why): void {
    $validate = fn () => ResponseValidator::validate($response(), contractOperation('/auth/me', 'GET'), '3.1.0');

    expect($validate)->toThrow(ResponseValidationException::class);
})->with([
    'missing required field' => [fn () => new JsonResponse(['data' => ['id' => '0199c0a6-5b3b-7000-8000-000000000001', 'name' => 'Ada']]), 'required'],
    'wrong type' => [fn () => new JsonResponse(['data' => ['id' => 1, 'name' => 'Ada', 'email' => 'ada@example.com', 'locale' => 'it', 'created_at' => '2026-10-12T10:00:00+00:00']]), 'type'],
    'enum violation' => [fn () => new JsonResponse(['data' => ['id' => '0199c0a6-5b3b-7000-8000-000000000001', 'name' => 'Ada', 'email' => 'ada@example.com', 'locale' => 'xx', 'created_at' => '2026-10-12T10:00:00+00:00']]), 'enum'],
    'missing data envelope' => [fn () => new JsonResponse(['id' => '0199c0a6-5b3b-7000-8000-000000000001']), 'envelope'],
    'status not in the contract' => [fn () => new JsonResponse(['data' => []], 202), 'status'],
    'wrong media type' => [fn () => new Response('plain', 200, ['Content-Type' => 'text/plain']), 'media type'],
]);

it('enforces problem details on error responses', function (): void {
    $operation = contractOperation('/auth/login', 'POST');

    ResponseValidator::validate(problemJson(['title' => 'Unauthenticated', 'status' => 401], 401), $operation, '3.1.0');

    expect(fn () => ResponseValidator::validate(problemJson(['status' => 401], 401), $operation, '3.1.0'))
        ->toThrow(ResponseValidationException::class);
});

it('validates 3.1 type arrays that include null', function (): void {
    $operation = contractOperation('/stats/summary', 'GET');
    $summary = fn (mixed $delta): array => ['data' => [
        'from' => '2026-10-01',
        'to' => '2026-10-12',
        'currency' => 'EUR',
        'total_cents' => 0,
        'count' => 0,
        'daily_average_cents' => 0,
        'previous_period' => ['total_cents' => 0, 'delta_percent' => $delta],
        'top_category' => null,
        'biggest_expense' => null,
    ]];

    ResponseValidator::validate(new JsonResponse($summary(null)), $operation, '3.1.0');
    ResponseValidator::validate(new JsonResponse($summary(12.5)), $operation, '3.1.0');

    $withDelta = fn (mixed $delta) => fn () => ResponseValidator::validate(new JsonResponse($summary($delta)), $operation, '3.1.0');

    expect($withDelta('not a number'))->toThrow(ResponseValidationException::class);

    $badTopCategory = $summary(null);
    $badTopCategory['data']['top_category'] = 42;

    expect(fn () => ResponseValidator::validate(new JsonResponse($badTopCategory), $operation, '3.1.0'))
        ->toThrow(ResponseValidationException::class);
});

it('keeps the backend copy of the contract in sync with the superproject', function (): void {
    $source = dirname(__DIR__, 3).'/contracts/openapi.yaml';

    expect(file_get_contents(base_path('contracts/openapi.yaml')))->toBe(file_get_contents($source));
})->skip(fn (): bool => ! is_file(dirname(__DIR__, 3).'/contracts/openapi.yaml'), 'Standalone checkout: no superproject next to this repository.');
