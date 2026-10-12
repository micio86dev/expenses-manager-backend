<?php

declare(strict_types=1);

namespace Database\Seeders\Reference;

use Illuminate\Database\Seeder;

/**
 * Reference data the application cannot work without (system categories arrive
 * with the categories feature). Every seeder called here MUST be idempotent and
 * safe to run in production: upsert by a natural key, never insert blindly.
 */
final class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Reference seeders are registered here as features introduce them.
        ]);
    }
}
