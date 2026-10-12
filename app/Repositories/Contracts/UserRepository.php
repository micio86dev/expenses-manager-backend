<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\ProfileChanges;
use App\Data\RegisterData;
use App\Models\User;

interface UserRepository
{
    public function create(RegisterData $data): User;

    public function findByEmail(string $email): ?User;

    public function update(User $user, ProfileChanges $changes): User;
}
