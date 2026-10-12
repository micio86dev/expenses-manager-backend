<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Data\ProfileChanges;
use App\Data\RegisterData;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Support\Str;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    public array $users = [];

    public function create(RegisterData $data): User
    {
        $user = new User;
        $user->forceFill([
            'id' => (string) Str::uuid(),
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
            'locale' => $data->locale,
        ]);

        return $this->users[$user->id] = $user;
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email === $email) {
                return $user;
            }
        }

        return null;
    }

    public function update(User $user, ProfileChanges $changes): User
    {
        return $this->users[$user->id] = $user->forceFill($changes->attributes());
    }
}
