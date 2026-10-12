<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use SensitiveParameter;

final readonly class AuthSession
{
    public function __construct(
        #[SensitiveParameter]
        public string $token,
        public User $user,
    ) {}
}
