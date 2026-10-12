<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Enums\CategoryIcon;
use App\Enums\SystemCategory;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Database\Seeders\Reference\SystemCategorySeeder;
use Illuminate\Database\Seeder;

/**
 * Sample data for local development only (see DatabaseSeeder). Built with
 * factories, never run in production. Deterministic: the same call yields the same shape.
 */
final class DemoSeeder extends Seeder
{
    public const string EMAIL = 'demo@example.com';

    public const string PASSWORD = 'demo-password';

    private const int EXPENSE_COUNT = 72;

    private const int MONTHS = 6;

    /**
     * @var list<array{0: string, 1: int, 2: string}> description, amount in cents, category key
     */
    private const array TEMPLATES = [
        ['Weekly groceries', 6420, 'groceries'],
        ['Pizza with friends', 2850, 'dining'],
        ['Train ticket', 1890, 'transport'],
        ['Rent', 85000, 'housing'],
        ['Electricity bill', 7430, 'utilities'],
        ['Pharmacy', 1245, 'health'],
        ['Cinema', 1600, 'leisure'],
        ['Dog food', 2399, 'pets'],
        ['Gym membership', 3500, 'gym'],
        ['Birthday present', 4200, 'other'],
    ];

    public function run(): void
    {
        if (User::query()->where('email', self::EMAIL)->exists()) {
            return;
        }

        $this->call(SystemCategorySeeder::class);

        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);

        $categories = $this->categoriesFor($user);
        $firstDay = now()->startOfMonth()->subMonths(self::MONTHS - 1);

        for ($i = 0; $i < self::EXPENSE_COUNT; $i++) {
            [$description, $cents, $key] = self::TEMPLATES[$i % count(self::TEMPLATES)];

            Expense::factory()
                ->ownedBy($user)
                ->inCategory($categories[$key])
                ->on($firstDay->addMonths($i % self::MONTHS)->addDays(($i * 5) % 27)->format('Y-m-d'))
                ->create([
                    'description' => $description,
                    'amount_cents' => $cents + ($i * 37) % 900,
                ]);
        }
    }

    /**
     * @return array<string, Category> system categories by translation key, plus the demo user's own
     */
    private function categoriesFor(User $user): array
    {
        $categories = [];

        foreach (SystemCategory::cases() as $system) {
            $categories[$system->value] = Category::query()
                ->withoutGlobalScopes()
                ->where('translation_key', $system->value)
                ->firstOrFail();
        }

        $categories['pets'] = Category::factory()->ownedBy($user)->create(['name' => 'Pets', 'color' => '#9C4221', 'icon' => CategoryIcon::Heart]);
        $categories['gym'] = Category::factory()->ownedBy($user)->create(['name' => 'Gym', 'color' => '#2C5282', 'icon' => CategoryIcon::Bolt]);

        return $categories;
    }
}
