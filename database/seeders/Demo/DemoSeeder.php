<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sample data for local development only (see DatabaseSeeder). Built with
 * factories, never run in production.
 */
final class DemoSeeder extends Seeder
{
    public const string EMAIL = 'demo@example.com';

    public const string PASSWORD = 'demo-password';

    public function run(): void
    {
        if (User::query()->where('email', self::EMAIL)->exists()) {
            return;
        }

        User::factory()->create([
            'name' => 'Demo User',
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);
    }
}
