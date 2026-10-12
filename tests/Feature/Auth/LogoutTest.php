<?php

declare(strict_types=1);

use App\Models\User;

it('revokes the current token', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')
        ->assertNoContent()
        ->assertValidResponse(204);

    expect($user->tokens()->count())->toBe(0);

    app('auth')->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('keeps other tokens of the same user alive', function (): void {
    $user = User::factory()->create();
    $first = $user->createToken('first')->plainTextToken;
    $second = $user->createToken('second')->plainTextToken;

    $this->withToken($first)->postJson('/api/v1/auth/logout')->assertNoContent();

    app('auth')->forgetGuards();
    $this->withToken($second)->getJson('/api/v1/auth/me')->assertOk();
});

it('requires authentication', function (): void {
    $this->postJson('/api/v1/auth/logout')
        ->assertUnauthorized()
        ->assertValidResponse(401)
        ->assertHeader('Content-Type', 'application/problem+json');
});
