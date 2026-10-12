<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\AuthSession;
use App\Data\RegisterData;
use App\Repositories\Contracts\AccessTokenRepository;
use App\Repositories\Contracts\UserRepository;

final readonly class RegisterUser
{
    public function __construct(
        private UserRepository $users,
        private AccessTokenRepository $tokens,
    ) {}

    public function __invoke(RegisterData $data): AuthSession
    {
        $user = $this->users->create($data);

        return new AuthSession($this->tokens->issue($user), $user);
    }
}
