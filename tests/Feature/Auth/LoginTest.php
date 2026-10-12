<?php

declare(strict_types=1);

use App\Models\User;

it('signs in with valid credentials', function (): void {
    $user = User::factory()->create(['email' => 'ada@example.com', 'password' => 'correct horse battery']);

    $response = $this->postJson('/api/v1/auth/login', ['email' => 'ada@example.com', 'password' => 'correct horse battery']);

    $response->assertOk()
        ->assertValidRequest()
        ->assertValidResponse(200)
        ->assertJsonPath('data.user.id', $user->id);

    $this->withToken($response->json('data.token'))->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('answers bad credentials with a 401 problem', function (string $email, string $password): void {
    User::factory()->create(['email' => 'ada@example.com', 'password' => 'correct horse battery']);

    $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => $password])
        ->assertUnauthorized()
        ->assertValidResponse(401)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 401);
})->with([
    'wrong password' => ['ada@example.com', 'not the password'],
    'unknown email' => ['nobody@example.com', 'correct horse battery'],
]);

it('does not reveal whether the email exists', function (): void {
    User::factory()->create(['email' => 'ada@example.com']);

    $known = $this->postJson('/api/v1/auth/login', ['email' => 'ada@example.com', 'password' => 'wrong-password'])->json();
    $unknown = $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password'])->json();

    expect($known)->toBe($unknown);
});

it('rejects a missing email or password', function (): void {
    $this->postJson('/api/v1/auth/login', [])
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

it('matches the email case-insensitively', function (): void {
    User::factory()->create(['email' => 'ada@example.com', 'password' => 'correct horse battery']);

    $this->postJson('/api/v1/auth/login', ['email' => 'ADA@example.com', 'password' => 'correct horse battery'])
        ->assertOk();
});
