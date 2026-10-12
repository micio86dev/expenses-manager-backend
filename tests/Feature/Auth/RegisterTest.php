<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * @return array<string, string>
 */
function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'correct horse battery',
    ], $overrides);
}

it('registers an account and returns a token with the user', function (): void {
    // No supported language requested, so the user gets the default locale.
    $response = $this->postJson('/api/v1/auth/register', registrationPayload(), ['Accept-Language' => 'de']);

    $response->assertCreated()
        ->assertValidRequest()
        ->assertValidResponse(201)
        ->assertJsonPath('data.user.email', 'ada@example.com')
        ->assertJsonPath('data.user.name', 'Ada Lovelace')
        ->assertJsonPath('data.user.locale', 'it')
        ->assertJsonMissingPath('data.user.password');

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();

    $user = User::query()->where('email', 'ada@example.com')->firstOrFail();
    expect(Hash::check('correct horse battery', $user->password))->toBeTrue()
        ->and($user->id)->toBeUuid();
});

it('stores the requested locale', function (): void {
    $this->postJson('/api/v1/auth/register', registrationPayload(['locale' => 'en']))
        ->assertCreated()
        ->assertValidResponse(201)
        ->assertJsonPath('data.user.locale', 'en');
});

it('lets the returned token authenticate follow-up requests', function (): void {
    $token = $this->postJson('/api/v1/auth/register', registrationPayload())->json('data.token');

    $this->withToken($token)->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'ada@example.com');
});

it('normalizes the email to lower case', function (): void {
    $this->postJson('/api/v1/auth/register', registrationPayload(['email' => 'Ada@Example.COM']))
        ->assertCreated()
        ->assertJsonPath('data.user.email', 'ada@example.com');
});

it('rejects invalid input with field keyed problem details', function (): void {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 422)
        ->assertJsonStructure(['title', 'status', 'detail', 'errors' => ['name', 'email', 'password']]);
});

it('rejects a duplicate email', function (): void {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->postJson('/api/v1/auth/register', registrationPayload())
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonValidationErrors(['email']);
});

it('rejects short passwords and unsupported locales', function (): void {
    $this->postJson('/api/v1/auth/register', registrationPayload(['password' => 'short', 'locale' => 'xx']))
        ->assertUnprocessable()
        ->assertValidResponse(422)
        ->assertJsonValidationErrors(['password', 'locale']);
});
