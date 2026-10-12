<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Repositories\Contracts\AccessTokenRepository;

final readonly class LogoutUser
{
    public function __construct(private AccessTokenRepository $tokens) {}

    public function __invoke(User $user): void
    {
        $this->tokens->revokeCurrent($user);
    }
}
