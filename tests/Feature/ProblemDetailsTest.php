<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    Route::get('/_probe/forbidden', fn () => throw new AuthorizationException('secret policy detail'));
    Route::get('/_probe/missing-model', fn () => throw (new ModelNotFoundException)->setModel('App\\Models\\Secret'));
    Route::get('/_probe/conflict', fn () => throw new ConflictHttpException('internal conflict detail'));
    Route::get('/_probe/boom', fn () => throw new RuntimeException('database password is hunter2'));
    Route::post('/_probe/validate', fn () => request()->validate(['amount' => ['required', 'integer']]));
    Route::get('/_probe/protected', fn () => 'secret')->middleware('auth:sanctum');
    Route::post('/_probe/post-only', fn () => 'ok');
});

it('renders every error status as problem details', function (string $method, string $uri, int $status): void {
    $response = $this->json($method, $uri)
        ->assertStatus($status)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', $status)
        ->assertJsonStructure(['type', 'title', 'status', 'detail']);

    expect($response->json('title'))->toBeString()->not->toBeEmpty()
        ->and($response->json('detail'))->toBeString()->not->toBeEmpty();
})->with([
    '401' => ['GET', '/_probe/protected', 401],
    '403' => ['GET', '/_probe/forbidden', 403],
    '404 route' => ['GET', '/api/v1/does-not-exist', 404],
    '404 model' => ['GET', '/_probe/missing-model', 404],
    '405' => ['GET', '/_probe/post-only', 405],
    '409' => ['GET', '/_probe/conflict', 409],
    '422' => ['POST', '/_probe/validate', 422],
    '500' => ['GET', '/_probe/boom', 500],
]);

it('keys validation errors by field', function (): void {
    $this->postJson('/_probe/validate', ['amount' => 'abc'])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['amount' => [0]]]);
});

it('never leaks internal exception messages', function (string $uri): void {
    $body = $this->getJson($uri)->getContent();

    expect($body)->not->toContain('hunter2')
        ->not->toContain('secret policy detail')
        ->not->toContain('App\\\\Models\\\\Secret')
        ->not->toContain('internal conflict detail');
})->with(['/_probe/boom', '/_probe/forbidden', '/_probe/missing-model', '/_probe/conflict']);

it('localizes the title and detail from Accept-Language', function (): void {
    $english = $this->getJson('/api/v1/does-not-exist', ['Accept-Language' => 'en'])->json();
    $italian = $this->getJson('/api/v1/does-not-exist', ['Accept-Language' => 'it'])->json();

    expect($english['title'])->toBe('Not found')
        ->and($italian['title'])->not->toBe($english['title'])
        ->and($italian['detail'])->not->toBe($english['detail']);
});

it('renders problem details even without a JSON Accept header', function (): void {
    $this->get('/_probe/protected', ['Accept' => 'text/html'])
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/problem+json');
});

it('does not expose stack traces when debugging is enabled', function (): void {
    config(['app.debug' => true]);

    $this->getJson('/_probe/boom')
        ->assertStatus(500)
        ->assertJsonMissingPath('trace')
        ->assertJsonMissingPath('exception');
});

it('falls back to a generic localized message for statuses without a dedicated entry', function (): void {
    Route::get('/_probe/teapot', fn () => throw new HttpException(418, 'I am a teapot'));

    $this->getJson('/_probe/teapot', ['Accept-Language' => 'en'])
        ->assertStatus(418)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('title', 'Request failed')
        ->assertJsonMissingPath('errors');
});

it('advertises the bearer scheme on 401 responses', function (): void {
    $this->getJson('/_probe/protected')->assertHeader('WWW-Authenticate', 'Bearer');
});

it('keeps the headers of http exceptions, such as Allow on 405', function (): void {
    $this->getJson('/_probe/post-only')->assertStatus(405)->assertHeader('Allow', 'POST');
});
