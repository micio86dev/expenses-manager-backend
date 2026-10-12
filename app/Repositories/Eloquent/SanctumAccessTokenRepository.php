<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\AccessTokenRepository;
use Laravel\Sanctum\PersonalAccessToken;

final class SanctumAccessTokenRepository implements AccessTokenRepository
{
    private const string TOKEN_NAME = 'api';

    public function issue(User $user): string
    {
        return $user->createToken(self::TOKEN_NAME)->plainTextToken;
    }

    public function revokeCurrent(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
