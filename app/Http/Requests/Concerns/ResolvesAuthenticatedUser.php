<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;

/**
 * For FormRequests of routes guarded by `auth:sanctum`: exposes the typed signed-in user.
 */
trait ResolvesAuthenticatedUser
{
    public function authenticatedUser(): User
    {
        $user = $this->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }
}
