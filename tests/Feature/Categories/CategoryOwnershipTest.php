<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;
use Database\Seeders\Reference\ReferenceSeeder;
use Laravel\Sanctum\Sanctum;

/*
 * Decision: system categories are shared rows with a NULL owner_id. Models that opt in with
 * IncludesUnownedRows see `owner_id = current OR owner_id IS NULL`, implemented once in OwnerScope.
 */

beforeEach(function (): void {
    $this->alice = User::factory()->create();
    $this->bob = User::factory()->create();
});

it('shows the system categories to every owner', function (): void {
    $this->seed(ReferenceSeeder::class);

    Sanctum::actingAs($this->alice);
    $forAlice = Category::query()->pluck('id')->all();

    Sanctum::actingAs($this->bob);
    $forBob = Category::query()->pluck('id')->all();

    expect($forAlice)->not->toBeEmpty()->and($forBob)->toEqualCanonicalizing($forAlice);
});

it('keeps custom categories private to their owner', function (): void {
    $alices = Category::factory()->ownedBy($this->alice)->create(['name' => 'Pets']);
    $bobs = Category::factory()->ownedBy($this->bob)->create(['name' => 'Gym']);

    Sanctum::actingAs($this->alice);

    expect(Category::query()->pluck('name')->all())->toBe(['Pets'])
        ->and(Category::query()->find($bobs->id))->toBeNull()
        ->and(Category::query()->find($alices->id))->not->toBeNull();

    Sanctum::actingAs($this->bob);

    expect(Category::query()->pluck('name')->all())->toBe(['Gym'])
        ->and(Category::query()->find($alices->id))->toBeNull();
});

it('stamps new custom categories with the current owner', function (): void {
    Sanctum::actingAs($this->alice);

    $category = Category::query()->create(['name' => 'Pets', 'color' => '#112233', 'icon' => 'tag']);

    expect($category->owner_id)->toBe($this->alice->id)
        ->and($category->is_system)->toBeFalse();
});

it('shows nothing when nobody is authenticated, system categories included', function (): void {
    $this->seed(ReferenceSeeder::class);

    expect(Category::query()->count())->toBe(0);
});

it('does not let an owner modify a category of another owner through a scoped query', function (): void {
    $bobs = Category::factory()->ownedBy($this->bob)->create(['name' => 'Gym']);

    Sanctum::actingAs($this->alice);

    expect(Category::query()->whereKey($bobs->id)->update(['name' => 'Hijacked']))->toBe(0);
});
