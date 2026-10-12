<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;

/**
 * A fixed spending history with known totals, shared by the statistics tests.
 *
 * Alice, EUR, range 2026-10-01..2026-10-31 (31 days):
 *   food      1000 (10-01), 2500 (10-05), 3000 (10-14)  = 6500, 3 expenses
 *   fun       4000 (10-05), 500 (10-31)                 = 4500, 2 expenses
 *   transport 1500 (10-12)                              = 1500, 1 expense
 *   total 12500, count 6, daily average 403 (12500 / 31 = 403.2), biggest = fun 4000
 * Previous period (same 31 days before): 2026-08-31..2026-09-30 = 10000 (two food expenses),
 *   so the change is +25.0 %.
 * Noise that must never be counted: 2026-08-30, 2026-11-01, a USD expense, and another owner's expenses.
 */
final class StatsDataset
{
    public function __construct(
        public readonly User $alice,
        public readonly User $bob,
        public readonly Category $food,
        public readonly Category $fun,
        public readonly Category $transport,
        public readonly Expense $biggest,
    ) {}

    public static function seed(): self
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $food = Category::factory()->ownedBy($alice)->create(['name' => 'Food']);
        $fun = Category::factory()->ownedBy($alice)->create(['name' => 'Fun']);
        $transport = Category::factory()->ownedBy($alice)->create(['name' => 'Transport']);

        $spend = static fn (User $owner, Category $category, string $date, int $cents, string $currency = 'EUR'): Expense => Expense::factory()
            ->ownedBy($owner)
            ->inCategory($category)
            ->on($date)
            ->create(['amount_cents' => $cents, 'currency' => $currency]);

        $spend($alice, $food, '2026-10-01', 1000);
        $spend($alice, $food, '2026-10-05', 2500);
        $biggest = $spend($alice, $fun, '2026-10-05', 4000);
        $spend($alice, $transport, '2026-10-12', 1500);
        $spend($alice, $food, '2026-10-14', 3000);
        $spend($alice, $fun, '2026-10-31', 500);

        $spend($alice, $food, '2026-08-31', 5000);
        $spend($alice, $food, '2026-09-15', 5000);

        $spend($alice, $food, '2026-08-30', 3000);
        $spend($alice, $food, '2026-11-01', 7000);
        $spend($alice, $food, '2026-10-10', 9999, 'USD');

        $bobsCategory = Category::factory()->ownedBy($bob)->create(['name' => 'Bobs']);
        $spend($bob, $bobsCategory, '2026-10-10', 88888);
        $spend($bob, $bobsCategory, '2026-09-10', 77777);

        return new self($alice, $bob, $food, $fun, $transport, $biggest);
    }
}
