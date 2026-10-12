<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Demo\DemoSeeder;
use Database\Seeders\Reference\ReferenceSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Runs a seeder directly: `db:seed` asks for confirmation in production.
 *
 * @param  class-string<Seeder>  $seeder
 */
function runSeeder(string $seeder): void
{
    app($seeder)->setContainer(app())->__invoke();
}

/**
 * @return array<string, list<array<string, mixed>>>
 */
function databaseSnapshot(): array
{
    $snapshot = [];

    foreach (Schema::getTableListing(schemaQualified: false) as $table) {
        $snapshot[$table] = DB::table($table)->orderBy(Schema::getColumnListing($table)[0])
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    return $snapshot;
}

it('runs the reference seeder idempotently', function (): void {
    runSeeder(ReferenceSeeder::class);
    $afterFirstRun = databaseSnapshot();

    runSeeder(ReferenceSeeder::class);

    expect(databaseSnapshot())->toBe($afterFirstRun);
});

it('runs the reference seeder in every environment', function (string $environment): void {
    $this->app->detectEnvironment(fn (): string => $environment);

    runSeeder(ReferenceSeeder::class);

    expect(true)->toBeTrue();
})->with(['local', 'testing', 'production']);

it('seeds demo data only in the local environment', function (): void {
    $this->app->detectEnvironment(fn (): string => 'local');

    runSeeder(DatabaseSeeder::class);

    expect(User::query()->where('email', DemoSeeder::EMAIL)->exists())->toBeTrue();
});

it('does not seed demo data outside local', function (string $environment): void {
    $this->app->detectEnvironment(fn (): string => $environment);

    runSeeder(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(0);
})->with(['testing', 'production', 'staging']);

it('runs the demo seeder idempotently', function (): void {
    runSeeder(DemoSeeder::class);
    runSeeder(DemoSeeder::class);

    expect(User::query()->where('email', DemoSeeder::EMAIL)->count())->toBe(1);
});

it('lets the demo user sign in', function (): void {
    runSeeder(DemoSeeder::class);

    $this->postJson('/api/v1/auth/login', ['email' => DemoSeeder::EMAIL, 'password' => DemoSeeder::PASSWORD])
        ->assertOk();
});
