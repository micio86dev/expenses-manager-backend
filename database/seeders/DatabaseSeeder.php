<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Demo\DemoSeeder;
use Database\Seeders\Reference\ReferenceSeeder;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceSeeder::class);

        if (app()->isLocal()) {
            $this->call(DemoSeeder::class);
        }
    }
}
