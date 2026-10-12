<?php

declare(strict_types=1);

use App\Models\User;

it('throttles repeated login attempts per email and ip', function (): void {
    config(['throttle.login' => 3]);
    User::factory()->create(['email' => 'ada@example.com']);

    foreach (range(1, 3) as $attempt) {
        $this->postJson('/api/v1/auth/login', ['email' => 'ada@example.com', 'password' => 'wrong-password'])
            ->assertUnauthorized();
    }

    $this->postJson('/api/v1/auth/login', ['email' => 'ada@example.com', 'password' => 'wrong-password'])
        ->assertStatus(429)
        ->assertValidResponse(429)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertHeader('Retry-After')
        ->assertJsonPath('status', 429);
});

it('keeps the login limit separate per email', function (): void {
    config(['throttle.login' => 1]);

    $this->postJson('/api/v1/auth/login', ['email' => 'a@example.com', 'password' => 'wrong-password'])->assertUnauthorized();
    $this->postJson('/api/v1/auth/login', ['email' => 'a@example.com', 'password' => 'wrong-password'])->assertStatus(429);
    $this->postJson('/api/v1/auth/login', ['email' => 'b@example.com', 'password' => 'wrong-password'])->assertUnauthorized();
});

it('throttles registrations per ip', function (): void {
    config(['throttle.register' => 2]);

    foreach (['a', 'b'] as $name) {
        $this->postJson('/api/v1/auth/register', [
            'name' => $name,
            'email' => "{$name}@example.com",
            'password' => 'correct horse battery',
        ])->assertCreated();
    }

    $this->postJson('/api/v1/auth/register', [
        'name' => 'c',
        'email' => 'c@example.com',
        'password' => 'correct horse battery',
    ])->assertStatus(429)->assertValidResponse(429)->assertHeader('Retry-After');
});

it('shares one login bucket between whitespace and case variants of an email', function (): void {
    config(['throttle.login' => 2]);

    foreach (['ada@example.com', ' ADA@example.com'] as $email) {
        $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => 'wrong-password'])->assertUnauthorized();
    }

    $this->postJson('/api/v1/auth/login', ['email' => "\tAda@example.com\n", 'password' => 'wrong-password'])->assertStatus(429);
});

it('throttles login attempts per ip across different emails', function (): void {
    config(['throttle.login' => 100, 'throttle.login_ip' => 3]);

    foreach (['a', 'b', 'c'] as $name) {
        $this->postJson('/api/v1/auth/login', ['email' => "{$name}@example.com", 'password' => 'wrong-password'])->assertUnauthorized();
    }

    $this->postJson('/api/v1/auth/login', ['email' => 'd@example.com', 'password' => 'wrong-password'])
        ->assertStatus(429)
        ->assertValidResponse(429);
});

it('answers a malformed email with a validation error instead of failing', function (mixed $email): void {
    $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => 'wrong-password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
})->with([
    'array' => [['x']],
    'nested array' => [[['x']]],
    'object' => [['a' => 'b']],
]);
