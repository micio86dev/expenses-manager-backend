<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use LogicException;

/**
 * @extends Factory<Expense>
 */
final class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'description' => fake()->sentence(3),
            'amount_cents' => fake()->numberBetween(50, 25_000),
            'currency' => 'EUR',
            // A category of the same owner, so the default expense is always consistent.
            'category_id' => static function (array $attributes): string {
                $owner = $attributes['owner_id'];

                if (! is_string($owner)) {
                    throw new LogicException('The expense owner must be resolved before its category.');
                }

                return Category::factory()->ownedBy($owner)->create()->id;
            },
            'spent_on' => fake()->dateTimeBetween('-6 months')->format('Y-m-d'),
        ];
    }

    public function ownedBy(User|string $owner): self
    {
        return $this->state(['owner_id' => $owner instanceof User ? $owner->id : $owner]);
    }

    public function inCategory(Category|string $category): self
    {
        return $this->state(['category_id' => $category instanceof Category ? $category->id : $category]);
    }

    public function on(string $date): self
    {
        return $this->state(['spent_on' => $date]);
    }
}
