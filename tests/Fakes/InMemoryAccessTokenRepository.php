<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Models\User;
use App\Repositories\Contracts\AccessTokenRepository;

final class InMemoryAccessTokenRepository implements AccessTokenRepository
{
    /** @var array<string, string> token => user id */
    public array $issued = [];

    /** @var list<string> */
    public array $revokedFor = [];

    public function issue(User $user): string
    {
        $token = 'token-'.(count($this->issued) + 1);
        $this->issued[$token] = $user->id;

        return $token;
    }

    public function revokeCurrent(User $user): void
    {
        $this->revokedFor[] = $user->id;
    }
}
