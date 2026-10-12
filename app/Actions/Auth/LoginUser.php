<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\AuthSession;
use App\Data\LoginData;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\Contracts\AccessTokenRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Contracts\Hashing\Hasher;

final readonly class LoginUser
{
    /** Hash of a random secret, checked against when the email is unknown. */
    private const string UNKNOWN_ACCOUNT_HASH = '$2y$12$ctbjZPpvA.VdGbMuxL7rK.igqw3PjaUqGKHYCKunYqv4MH7X8.nyu';

    public function __construct(
        private UserRepository $users,
        private AccessTokenRepository $tokens,
        private Hasher $hasher,
    ) {}

    /**
     * @throws InvalidCredentialsException
     */
    public function __invoke(LoginData $data): AuthSession
    {
        $user = $this->users->findByEmail($data->email);

        // Always hash-check, even for unknown emails, so timing does not reveal accounts.
        $valid = $this->hasher->check($data->password, $user instanceof User ? $user->password : self::UNKNOWN_ACCOUNT_HASH);

        if ($user === null || ! $valid) {
            throw new InvalidCredentialsException;
        }

        return new AuthSession($this->tokens->issue($user), $user);
    }
}
