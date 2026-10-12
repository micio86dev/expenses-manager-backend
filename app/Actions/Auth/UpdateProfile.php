<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\ProfileChanges;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;

final readonly class UpdateProfile
{
    public function __construct(private UserRepository $users) {}

    public function __invoke(User $user, ProfileChanges $changes): User
    {
        return $this->users->update($user, $changes);
    }
}
