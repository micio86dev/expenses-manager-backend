<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Data\ProfileChanges;
use App\Data\RegisterData;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;

final class EloquentUserRepository implements UserRepository
{
    public function create(RegisterData $data): User
    {
        return User::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
            'locale' => $data->locale,
        ]);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function update(User $user, ProfileChanges $changes): User
    {
        $user->fill($changes->attributes())->save();

        return $user;
    }
}
