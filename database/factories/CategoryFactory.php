<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryIcon;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(CategoryIcon::cases()),
            'is_system' => false,
            'translation_key' => null,
            'archived_at' => null,
        ];
    }

    public function ownedBy(User|string $owner): self
    {
        return $this->state(['owner_id' => $owner instanceof User ? $owner->id : $owner]);
    }

    public function system(string $translationKey = 'other'): self
    {
        return $this->state([
            'owner_id' => null,
            'is_system' => true,
            'translation_key' => $translationKey,
            'name' => $translationKey,
        ]);
    }

    public function archived(): self
    {
        return $this->state(['archived_at' => now()]);
    }
}
