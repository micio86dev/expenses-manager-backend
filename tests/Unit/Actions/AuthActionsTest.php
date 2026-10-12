<?php

declare(strict_types=1);

use App\Actions\Auth\LoginUser;
use App\Actions\Auth\LogoutUser;
use App\Actions\Auth\RegisterUser;
use App\Actions\Auth\UpdateProfile;
use App\Data\LoginData;
use App\Data\ProfileChanges;
use App\Data\RegisterData;
use App\Exceptions\InvalidCredentialsException;
use Illuminate\Hashing\BcryptHasher;
use Tests\Fakes\InMemoryAccessTokenRepository;
use Tests\Fakes\InMemoryUserRepository;

beforeEach(function (): void {
    $this->users = new InMemoryUserRepository;
    $this->tokens = new InMemoryAccessTokenRepository;
    $this->hasher = new BcryptHasher(['rounds' => 4]);
});

it('registers a user and issues a token', function (): void {
    $session = (new RegisterUser($this->users, $this->tokens))(
        new RegisterData('Ada', 'ada@example.com', 'secret-password', 'en'),
    );

    expect($session->user->email)->toBe('ada@example.com')
        ->and($session->token)->toBe('token-1')
        ->and($this->tokens->issued)->toBe(['token-1' => $session->user->id]);
});

it('logs in with the right password', function (): void {
    $this->users->create(new RegisterData('Ada', 'ada@example.com', $this->hasher->make('secret-password'), 'en'));

    $session = (new LoginUser($this->users, $this->tokens, $this->hasher))(
        new LoginData('ada@example.com', 'secret-password'),
    );

    expect($session->user->name)->toBe('Ada')->and($session->token)->toBe('token-1');
});

it('refuses a wrong password or unknown email without issuing a token', function (string $email, string $password): void {
    $this->users->create(new RegisterData('Ada', 'ada@example.com', $this->hasher->make('secret-password'), 'en'));

    $login = new LoginUser($this->users, $this->tokens, $this->hasher);

    expect(fn () => $login(new LoginData($email, $password)))->toThrow(InvalidCredentialsException::class)
        ->and($this->tokens->issued)->toBe([]);
})->with([
    'wrong password' => ['ada@example.com', 'nope'],
    'unknown email' => ['nobody@example.com', 'secret-password'],
]);

it('revokes the current token on logout', function (): void {
    $user = $this->users->create(new RegisterData('Ada', 'ada@example.com', 'x', 'en'));

    (new LogoutUser($this->tokens))($user);

    expect($this->tokens->revokedFor)->toBe([$user->id]);
});

it('applies only the provided profile changes', function (): void {
    $user = $this->users->create(new RegisterData('Ada', 'ada@example.com', 'x', 'it'));

    $updated = (new UpdateProfile($this->users))($user, new ProfileChanges(locale: 'en'));

    expect($updated->name)->toBe('Ada')->and($updated->locale)->toBe('en');
});
