<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

function problemTitle(string $acceptLanguage): string
{
    return test()->getJson('/api/v1/does-not-exist', ['Accept-Language' => $acceptLanguage])
        ->assertNotFound()
        ->assertValidResponse(404)
        ->json('title');
}

it('resolves the locale from Accept-Language', function (string $header, string $locale): void {
    expect(problemTitle($header))->toBe(__('errors.404.title', [], $locale));
})->with([
    'italian' => ['it', 'it'],
    'english' => ['en', 'en'],
    'region subtag falls back to the primary language' => ['en-GB', 'en'],
    'quality values decide the order' => ['it;q=0.4, en;q=0.9', 'en'],
    'first supported language wins' => ['fr-FR, it;q=0.8, en;q=0.5', 'it'],
    'unsupported languages use the default' => ['de', 'it'],
    'wildcard uses the default' => ['*', 'it'],
]);

it('falls back to the signed-in user locale when no supported language is requested', function (): void {
    Sanctum::actingAs(User::factory()->create(['locale' => 'en']));

    $this->patchJson('/api/v1/auth/me', [], ['Accept-Language' => 'de'])
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonPath('title', __('errors.422.title', [], 'en'));
});

it('lets Accept-Language win over the stored user locale', function (): void {
    Sanctum::actingAs(User::factory()->create(['locale' => 'en']));

    $this->patchJson('/api/v1/auth/me', [], ['Accept-Language' => 'it'])
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonPath('title', __('errors.422.title', [], 'it'));
});

it('localizes validation messages', function (string $locale): void {
    $this->postJson('/api/v1/auth/register', [], ['Accept-Language' => $locale])
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonPath('errors.name.0', __('validation.required', ['attribute' => __('validation.attributes.name', [], $locale)], $locale));
})->with([
    'italian' => ['it'],
    'english' => ['en'],
]);

it('localizes the bad credentials detail', function (string $locale): void {
    $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'whatever-1'], ['Accept-Language' => $locale])
        ->assertUnauthorized()
        ->assertValidResponse(401)
        ->assertJsonPath('detail', __('auth.failed', [], $locale));
})->with([
    'italian' => ['it'],
    'english' => ['en'],
]);
