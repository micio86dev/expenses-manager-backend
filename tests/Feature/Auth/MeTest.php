<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns the signed-in user', function (): void {
    $user = User::factory()->create(['name' => 'Ada', 'locale' => 'en']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertValidResponse(200)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.name', 'Ada')
        ->assertJsonPath('data.locale', 'en');
});

it('requires authentication to read the profile', function (): void {
    $this->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertValidResponse(401);
});

it('rejects an invalid bearer token', function (): void {
    $this->withToken('999|not-a-real-token')->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/problem+json');
});

it('updates the name and locale', function (): void {
    $user = User::factory()->create(['name' => 'Ada', 'locale' => 'it']);
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/auth/me', ['name' => 'Ada Lovelace', 'locale' => 'en'])
        ->assertOk()
        ->assertValidRequest()
        ->assertValidResponse(200)
        ->assertJsonPath('data.name', 'Ada Lovelace')
        ->assertJsonPath('data.locale', 'en');

    expect($user->refresh()->name)->toBe('Ada Lovelace')->and($user->locale)->toBe('en');
});

it('updates a single field and leaves the other untouched', function (): void {
    $user = User::factory()->create(['name' => 'Ada', 'locale' => 'it']);
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/auth/me', ['locale' => 'en'])->assertOk();

    expect($user->refresh()->name)->toBe('Ada')->and($user->locale)->toBe('en');
});

it('rejects an empty update and unsupported locales', function (array $payload, array $fields): void {
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson('/api/v1/auth/me', $payload)
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonValidationErrors($fields);
})->with([
    'empty body' => [[], ['name', 'locale']],
    'bad locale' => [['locale' => 'xx'], ['locale']],
    'empty name' => [['name' => ''], ['name']],
]);

it('requires authentication to update the profile', function (): void {
    $this->patchJson('/api/v1/auth/me', ['name' => 'Ada'])
        ->assertUnauthorized()
        ->assertValidResponse(401);
});
