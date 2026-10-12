<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;

interface AccessTokenRepository
{
    /**
     * Issues a personal access token and returns its plain text value (shown once).
     */
    public function issue(User $user): string;

    /**
     * Revokes the token that authenticated the current request, if any.
     */
    public function revokeCurrent(User $user): void;
}
