<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Database\Seeders\Reference\ReferenceSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->alice = User::factory()->create();
    $this->bob = User::factory()->create();
    $this->seed(ReferenceSeeder::class);
    Sanctum::actingAs($this->alice);
});

function systemCategory(string $key = 'groceries'): Category
{
    return Category::query()->withoutGlobalScopes()->where('translation_key', $key)->firstOrFail();
}

/**
 * @return array<string, string>
 */
function categoryPayload(array $overrides = []): array
{
    return [...['name' => 'Pets', 'color' => '#AA33CC', 'icon' => 'heart'], ...$overrides];
}

describe('list', function (): void {
    it('lists system and own categories sorted by name, never those of others', function (): void {
        Category::factory()->ownedBy($this->alice)->create(['name' => 'Zoo']);
        Category::factory()->ownedBy($this->alice)->create(['name' => 'Aquarium']);
        Category::factory()->ownedBy($this->bob)->create(['name' => 'Secret']);

        $response = $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/categories')
            ->assertOk()
            ->assertValidResponse(200);

        $names = collect($response->json('data'))->pluck('name')->all();

        expect($names)->toHaveCount(10)
            ->and($names)->not->toContain('Secret')
            ->and($names)->toBe(collect($names)->sortBy(fn (string $name): string => mb_strtolower($name))->values()->all());
    });

    it('translates system category names with the request locale', function (): void {
        $italian = collect($this->withHeader('Accept-Language', 'it')->getJson('/api/v1/categories')->json('data'))->pluck('name');
        $english = collect($this->withHeader('Accept-Language', 'en')->getJson('/api/v1/categories')->json('data'))->pluck('name');

        expect($italian)->toContain('Spesa', 'Trasporti')->and($english)->toContain('Groceries', 'Transport');
    });

    it('hides archived categories unless asked', function (): void {
        Category::factory()->ownedBy($this->alice)->archived()->create(['name' => 'Old']);

        $without = $this->getJson('/api/v1/categories')->assertValidResponse(200)->json('data');
        $with = $this->getJson('/api/v1/categories?include_archived=true')->assertValidResponse(200)->json('data');

        expect(collect($without)->pluck('name'))->not->toContain('Old')
            ->and(collect($with)->pluck('name'))->toContain('Old')
            ->and(collect($with)->firstWhere('name', 'Old')['archived'])->toBeTrue();
    });

    it('treats a malformed include_archived flag as false', function (): void {
        Category::factory()->ownedBy($this->alice)->archived()->create(['name' => 'Old']);

        $names = collect($this->getJson('/api/v1/categories?include_archived=maybe')->assertOk()->assertValidResponse(200)->json('data'))->pluck('name');

        expect($names)->not->toContain('Old');
    });
});

describe('create', function (): void {
    it('creates a custom category owned by the caller', function (): void {
        $response = $this->postJson('/api/v1/categories', categoryPayload())
            ->assertCreated()
            ->assertValidRequest()
            ->assertValidResponse(201)
            ->assertJsonPath('data.name', 'Pets')
            ->assertJsonPath('data.color', '#AA33CC')
            ->assertJsonPath('data.icon', 'heart')
            ->assertJsonPath('data.is_system', false)
            ->assertJsonPath('data.archived', false);

        $category = Category::query()->findOrFail($response->json('data.id'));

        expect($category->owner_id)->toBe($this->alice->id);
    });

    it('rejects invalid input', function (array $payload, string $field): void {
        $this->postJson('/api/v1/categories', $payload)
            ->assertUnprocessable()
            ->assertValidResponse(422)
            ->assertJsonValidationErrors($field);
    })->with([
        'missing name' => [fn () => categoryPayload(['name' => null]), 'name'],
        'name too long' => [fn () => categoryPayload(['name' => str_repeat('a', 61)]), 'name'],
        'bad color' => [fn () => categoryPayload(['color' => 'red']), 'color'],
        'short color' => [fn () => categoryPayload(['color' => '#FFF']), 'color'],
        'unknown icon' => [fn () => categoryPayload(['icon' => 'rocket']), 'icon'],
        'missing icon' => [fn () => categoryPayload(['icon' => null]), 'icon'],
    ]);

    it('rejects a duplicate name for the same owner only', function (): void {
        Category::factory()->ownedBy($this->alice)->create(['name' => 'Pets']);
        Category::factory()->ownedBy($this->bob)->create(['name' => 'Gym']);

        $this->postJson('/api/v1/categories', categoryPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->postJson('/api/v1/categories', categoryPayload(['name' => 'Gym']))->assertCreated();
    });

    it('returns localized validation messages', function (): void {
        $this->withHeader('Accept-Language', 'it')->postJson('/api/v1/categories', categoryPayload(['name' => null]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Il campo nome è obbligatorio.');
    });
});

describe('show', function (): void {
    it('shows an own or a system category', function (): void {
        $own = Category::factory()->ownedBy($this->alice)->create(['name' => 'Pets']);

        $this->getJson("/api/v1/categories/{$own->id}")->assertOk()->assertValidResponse(200)->assertJsonPath('data.id', $own->id);
        $this->getJson('/api/v1/categories/'.systemCategory()->id)->assertOk()->assertValidResponse(200)->assertJsonPath('data.is_system', true);
    });

    it('answers 404 for the category of another owner or a malformed id', function (): void {
        $bobs = Category::factory()->ownedBy($this->bob)->create();

        $this->getJson("/api/v1/categories/{$bobs->id}")->assertNotFound()->assertValidResponse(404);
        $this->getJson('/api/v1/categories/not-a-uuid')->assertNotFound();
        $this->getJson('/api/v1/categories/'.fake()->uuid())->assertNotFound();
    });
});

describe('update', function (): void {
    it('updates an own category', function (): void {
        $own = Category::factory()->ownedBy($this->alice)->create(['name' => 'Pets']);

        $this->putJson("/api/v1/categories/{$own->id}", categoryPayload(['name' => 'Animals', 'icon' => 'tag']))
            ->assertOk()
            ->assertValidRequest()
            ->assertValidResponse(200)
            ->assertJsonPath('data.name', 'Animals')
            ->assertJsonPath('data.icon', 'tag');
    });

    it('lets the category keep its own name', function (): void {
        $own = Category::factory()->ownedBy($this->alice)->create(['name' => 'Pets']);

        $this->putJson("/api/v1/categories/{$own->id}", categoryPayload(['color' => '#000000']))->assertOk();
    });

    it('rejects a name already used by another category of the owner', function (): void {
        Category::factory()->ownedBy($this->alice)->create(['name' => 'Gym']);
        $own = Category::factory()->ownedBy($this->alice)->create(['name' => 'Pets']);

        $this->putJson("/api/v1/categories/{$own->id}", categoryPayload(['name' => 'Gym']))
            ->assertUnprocessable()
            ->assertValidResponse(422)
            ->assertJsonValidationErrors('name');
    });

    it('forbids changing a system category', function (): void {
        $system = systemCategory();

        $this->putJson("/api/v1/categories/{$system->id}", categoryPayload())
            ->assertForbidden()
            ->assertValidResponse(403)
            ->assertHeader('Content-Type', 'application/problem+json');

        expect($system->refresh()->color)->not->toBe('#AA33CC');
    });

    it('answers 404 for the category of another owner', function (): void {
        $bobs = Category::factory()->ownedBy($this->bob)->create(['name' => 'Gym']);

        $this->putJson("/api/v1/categories/{$bobs->id}", categoryPayload())->assertNotFound()->assertValidResponse(404);

        expect($bobs->refresh()->name)->toBe('Gym');
    });
});

describe('delete', function (): void {
    it('deletes an unused own category', function (): void {
        $own = Category::factory()->ownedBy($this->alice)->create();

        $this->deleteJson("/api/v1/categories/{$own->id}")->assertNoContent();

        expect(Category::query()->find($own->id))->toBeNull();
    });

    it('refuses to delete a category used by expenses', function (): void {
        $own = Category::factory()->ownedBy($this->alice)->create();
        Expense::factory()->ownedBy($this->alice)->inCategory($own)->create();

        $this->deleteJson("/api/v1/categories/{$own->id}")
            ->assertConflict()
            ->assertValidResponse(409);

        expect(Category::query()->find($own->id))->not->toBeNull();
    });

    it('forbids deleting a system category', function (): void {
        $this->deleteJson('/api/v1/categories/'.systemCategory()->id)->assertForbidden()->assertValidResponse(403);
    });

    it('answers 404 for the category of another owner', function (): void {
        $bobs = Category::factory()->ownedBy($this->bob)->create();

        $this->deleteJson("/api/v1/categories/{$bobs->id}")->assertNotFound()->assertValidResponse(404);

        expect(Category::query()->withoutGlobalScopes()->find($bobs->id))->not->toBeNull();
    });
});

describe('archive', function (): void {
    it('archives and restores a category', function (): void {
        $own = Category::factory()->ownedBy($this->alice)->create();

        $this->postJson("/api/v1/categories/{$own->id}/archive")
            ->assertOk()->assertValidResponse(200)->assertJsonPath('data.archived', true);

        $this->postJson("/api/v1/categories/{$own->id}/unarchive")
            ->assertOk()->assertValidResponse(200)->assertJsonPath('data.archived', false);
    });

    it('is idempotent and keeps the first archive time', function (): void {
        $own = Category::factory()->ownedBy($this->alice)->create();

        $this->postJson("/api/v1/categories/{$own->id}/archive")->assertOk();
        $first = $own->refresh()->archived_at;

        $this->travel(5)->minutes();
        $this->postJson("/api/v1/categories/{$own->id}/archive")->assertOk()->assertJsonPath('data.archived', true);

        expect($first)->not->toBeNull()
            ->and($own->refresh()->archived_at?->equalTo($first))->toBeTrue();

        $this->postJson("/api/v1/categories/{$own->id}/unarchive")->assertOk();
        $this->postJson("/api/v1/categories/{$own->id}/unarchive")->assertOk()->assertJsonPath('data.archived', false);
    });

    it('does not archive shared system categories, which would hide them from everyone', function (): void {
        $system = systemCategory();

        // Not asserted against the contract: archive/unarchive document no 403 yet (see the phase report).
        $this->postJson("/api/v1/categories/{$system->id}/archive")->assertForbidden();
        $this->postJson("/api/v1/categories/{$system->id}/unarchive")->assertForbidden();

        expect($system->refresh()->archived_at)->toBeNull();
    });

    it('answers 404 for the category of another owner', function (): void {
        $bobs = Category::factory()->ownedBy($this->bob)->create();

        $this->postJson("/api/v1/categories/{$bobs->id}/archive")->assertNotFound()->assertValidResponse(404);
        $this->postJson("/api/v1/categories/{$bobs->id}/unarchive")->assertNotFound()->assertValidResponse(404);
    });
});
