<?php

declare(strict_types=1);

namespace App\Support\Owner;

use Illuminate\Contracts\Auth\Factory as AuthFactory;

final readonly class AuthenticatedUserOwner implements CurrentOwner
{
    private const string GUARD = 'sanctum';

    public function __construct(private AuthFactory $auth) {}

    public function id(): ?string
    {
        $id = $this->auth->guard(self::GUARD)->id();

        return $id === null || $id === '' ? null : (string) $id;
    }
}
