<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

function problemTitle(string $acceptLanguage): string
{
    return test()->getJson('/api/v1/does-not-exist', ['Accept-Language' => $acceptLanguage])->json('title');
}

it('resolves the locale from Accept-Language', function (string $header, string $expected): void {
    expect(problemTitle($header))->toBe($expected);
})->with([
    'italian' => ['it', 'Non trovato'],
    'english' => ['en', 'Not found'],
    'region subtag falls back to the primary language' => ['en-GB', 'Not found'],
    'quality values decide the order' => ['it;q=0.4, en;q=0.9', 'Not found'],
    'first supported language wins' => ['fr-FR, it;q=0.8, en;q=0.5', 'Non trovato'],
    'unsupported languages use the default' => ['de', 'Non trovato'],
    'wildcard uses the default' => ['*', 'Non trovato'],
]);

it('falls back to the signed-in user locale when no supported language is requested', function (): void {
    Sanctum::actingAs(User::factory()->create(['locale' => 'en']));

    $this->patchJson('/api/v1/auth/me', [], ['Accept-Language' => 'de'])
        ->assertUnprocessable()
        ->assertJsonPath('title', 'Validation failed');
});

it('lets Accept-Language win over the stored user locale', function (): void {
    Sanctum::actingAs(User::factory()->create(['locale' => 'en']));

    $this->patchJson('/api/v1/auth/me', [], ['Accept-Language' => 'it'])
        ->assertUnprocessable()
        ->assertJsonPath('title', 'Validazione non riuscita');
});

it('localizes validation messages', function (string $locale, string $expected): void {
    $this->postJson('/api/v1/auth/register', [], ['Accept-Language' => $locale])
        ->assertUnprocessable()
        ->assertJsonPath('errors.name.0', $expected);
})->with([
    'italian' => ['it', 'Il campo nome è obbligatorio.'],
    'english' => ['en', 'The name field is required.'],
]);

it('localizes the bad credentials detail', function (string $locale, string $expected): void {
    $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'whatever-1'], ['Accept-Language' => $locale])
        ->assertUnauthorized()
        ->assertJsonPath('detail', $expected);
})->with([
    'italian' => ['it', 'Le credenziali fornite non sono corrette.'],
    'english' => ['en', 'These credentials do not match our records.'],
]);
