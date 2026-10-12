<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Database\Seeders\Reference\ReferenceSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->alice = User::factory()->create();
    $this->bob = User::factory()->create();
    $this->seed(ReferenceSeeder::class);
    $this->groceries = Category::query()->withoutGlobalScopes()->where('translation_key', 'groceries')->firstOrFail();
    $this->pets = Category::factory()->ownedBy($this->alice)->create(['name' => 'Pets']);
    Sanctum::actingAs($this->alice);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function expensePayload(array $overrides = []): array
{
    return [...[
        'description' => 'Weekly shop',
        'amount_cents' => 4599,
        'currency' => 'EUR',
        'category_id' => test()->pets->id,
        'spent_on' => '2026-10-12',
    ], ...$overrides];
}

describe('create', function (): void {
    it('records an expense for the caller and points to it', function (): void {
        $response = $this->postJson('/api/v1/expenses', expensePayload())
            ->assertCreated()
            ->assertValidRequest()
            ->assertValidResponse(201)
            ->assertJsonPath('data.description', 'Weekly shop')
            ->assertJsonPath('data.amount_cents', 4599)
            ->assertJsonPath('data.currency', 'EUR')
            ->assertJsonPath('data.category_id', $this->pets->id)
            ->assertJsonPath('data.spent_on', '2026-10-12');

        $id = $response->json('data.id');
        $response->assertHeader('Location', url("/api/v1/expenses/{$id}"));

        expect(Expense::query()->findOrFail($id)->owner_id)->toBe($this->alice->id);
    });

    it('defaults the currency to EUR and accepts system categories', function (): void {
        $payload = expensePayload(['category_id' => $this->groceries->id]);
        unset($payload['currency']);

        $this->postJson('/api/v1/expenses', $payload)
            ->assertCreated()
            ->assertValidResponse(201)
            ->assertJsonPath('data.currency', 'EUR');
    });

    it('stores other currencies and the largest amounts as integers', function (): void {
        $this->postJson('/api/v1/expenses', expensePayload(['currency' => 'USD', 'amount_cents' => 9_007_199_254_740_993]))
            ->assertCreated()
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.amount_cents', 9_007_199_254_740_993);
    });

    it('rejects invalid input', function (array $overrides, string $field): void {
        $this->postJson('/api/v1/expenses', expensePayload($overrides))
            ->assertUnprocessable()
            ->assertValidResponse(422)
            ->assertJsonValidationErrors($field);
    })->with([
        'missing description' => [['description' => null], 'description'],
        'blank description' => [['description' => '   '], 'description'],
        'description too long' => [['description' => str_repeat('a', 256)], 'description'],
        'zero amount' => [['amount_cents' => 0], 'amount_cents'],
        'negative amount' => [['amount_cents' => -5], 'amount_cents'],
        'fractional amount' => [['amount_cents' => 10.5], 'amount_cents'],
        'amount as string' => [['amount_cents' => '1000'], 'amount_cents'],
        'missing amount' => [['amount_cents' => null], 'amount_cents'],
        'lowercase currency' => [['currency' => 'eur'], 'currency'],
        'long currency' => [['currency' => 'EURO'], 'currency'],
        'missing category' => [['category_id' => null], 'category_id'],
        'category not a uuid' => [['category_id' => 'food'], 'category_id'],
        'unknown category' => [['category_id' => '0199c0a6-5b3b-7000-8000-000000000001'], 'category_id'],
        'missing date' => [['spent_on' => null], 'spent_on'],
        'wrong date format' => [['spent_on' => '12/10/2026'], 'spent_on'],
        'impossible date' => [['spent_on' => '2026-02-30'], 'spent_on'],
    ]);

    it('rejects the category of another owner', function (): void {
        $bobs = Category::factory()->ownedBy($this->bob)->create();

        $this->postJson('/api/v1/expenses', expensePayload(['category_id' => $bobs->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    });

    it('rejects an archived category for a new expense', function (): void {
        $archived = Category::factory()->ownedBy($this->alice)->archived()->create();

        $this->postJson('/api/v1/expenses', expensePayload(['category_id' => $archived->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    });

    it('localizes validation errors', function (): void {
        $this->withHeader('Accept-Language', 'it')->postJson('/api/v1/expenses', expensePayload(['amount_cents' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount_cents');

        $this->withHeader('Accept-Language', 'it')->postJson('/api/v1/expenses', expensePayload(['category_id' => '0199c0a6-5b3b-7000-8000-000000000001']))
            ->assertJsonPath('errors.category_id.0', 'La categoria selezionata non esiste o è archiviata.');
    });
});

describe('show', function (): void {
    it('shows an own expense', function (): void {
        $expense = Expense::factory()->ownedBy($this->alice)->inCategory($this->pets)->create();

        $this->getJson("/api/v1/expenses/{$expense->id}")
            ->assertOk()
            ->assertValidResponse(200)
            ->assertJsonPath('data.id', $expense->id);
    });

    it('answers 404 for the expense of another owner, an unknown id or a malformed id', function (): void {
        $bobs = Expense::factory()->ownedBy($this->bob)->create();

        $this->getJson("/api/v1/expenses/{$bobs->id}")->assertNotFound()->assertValidResponse(404);
        $this->getJson('/api/v1/expenses/0199c0a6-5b3b-7000-8000-000000000001')->assertNotFound();
        $this->getJson('/api/v1/expenses/nope')->assertNotFound();
    });
});

describe('update', function (): void {
    it('replaces an own expense', function (): void {
        $expense = Expense::factory()->ownedBy($this->alice)->inCategory($this->pets)->create();

        $this->putJson("/api/v1/expenses/{$expense->id}", expensePayload([
            'description' => 'Vet', 'amount_cents' => 8000, 'currency' => 'GBP',
            'category_id' => $this->groceries->id, 'spent_on' => '2026-09-01',
        ]))
            ->assertOk()
            ->assertValidRequest()
            ->assertValidResponse(200)
            ->assertJsonPath('data.description', 'Vet')
            ->assertJsonPath('data.amount_cents', 8000)
            ->assertJsonPath('data.currency', 'GBP')
            ->assertJsonPath('data.category_id', $this->groceries->id)
            ->assertJsonPath('data.spent_on', '2026-09-01');

        $fresh = $expense->refresh();

        expect($fresh->description)->toBe('Vet')->and($fresh->owner_id)->toBe($this->alice->id);
    });

    it('rejects invalid input', function (): void {
        $expense = Expense::factory()->ownedBy($this->alice)->inCategory($this->pets)->create();

        $this->putJson("/api/v1/expenses/{$expense->id}", expensePayload(['amount_cents' => 0, 'spent_on' => 'x']))
            ->assertUnprocessable()
            ->assertValidResponse(422)
            ->assertJsonValidationErrors(['amount_cents', 'spent_on']);
    });

    // Rule: archiving a category only blocks it for NEW selections. An expense that already uses
    // an archived category can still be edited and keeps that category; moving any expense to a
    // different archived category is rejected.
    it('keeps an expense editable after its category was archived', function (): void {
        $expense = Expense::factory()->ownedBy($this->alice)->inCategory($this->pets)->create();
        $this->postJson("/api/v1/categories/{$this->pets->id}/archive")->assertOk();

        $this->putJson("/api/v1/expenses/{$expense->id}", expensePayload(['description' => 'Edited', 'category_id' => $this->pets->id]))
            ->assertOk()
            ->assertJsonPath('data.description', 'Edited')
            ->assertJsonPath('data.category_id', $this->pets->id);
    });

    it('does not move an expense into another archived category', function (): void {
        $expense = Expense::factory()->ownedBy($this->alice)->inCategory($this->pets)->create();
        $archived = Category::factory()->ownedBy($this->alice)->archived()->create();

        $this->putJson("/api/v1/expenses/{$expense->id}", expensePayload(['category_id' => $archived->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    });

    it('does not let an archived category be smuggled in through another expense', function (): void {
        $expense = Expense::factory()->ownedBy($this->alice)->inCategory($this->groceries)->create();
        $other = Expense::factory()->ownedBy($this->alice)->inCategory($this->pets)->create();
        $this->postJson("/api/v1/categories/{$this->pets->id}/archive")->assertOk();

        $this->putJson("/api/v1/expenses/{$expense->id}", expensePayload(['category_id' => $this->pets->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');

        expect($other->refresh()->category_id)->toBe($this->pets->id);
    });

    it('answers 404 for the expense of another owner and leaves it untouched', function (): void {
        $bobs = Expense::factory()->ownedBy($this->bob)->create(['description' => 'Bob lunch']);

        $this->putJson("/api/v1/expenses/{$bobs->id}", expensePayload())->assertNotFound()->assertValidResponse(404);

        expect(Expense::query()->withoutGlobalScopes()->findOrFail($bobs->id)->description)->toBe('Bob lunch');
    });
});

describe('delete', function (): void {
    it('deletes an own expense', function (): void {
        $expense = Expense::factory()->ownedBy($this->alice)->create();

        $this->deleteJson("/api/v1/expenses/{$expense->id}")->assertNoContent();

        expect(Expense::query()->find($expense->id))->toBeNull();
    });

    it('answers 404 for the expense of another owner and keeps it', function (): void {
        $bobs = Expense::factory()->ownedBy($this->bob)->create();

        $this->deleteJson("/api/v1/expenses/{$bobs->id}")->assertNotFound()->assertValidResponse(404);

        expect(Expense::query()->withoutGlobalScopes()->find($bobs->id))->not->toBeNull();
    });
});

describe('calendar date storage', function (): void {
    it('stores spent_on as a plain calendar date', function (): void {
        $id = $this->postJson('/api/v1/expenses', expensePayload(['spent_on' => '2026-10-12']))
            ->assertCreated()
            ->json('data.id');

        $raw = DB::table('expenses')->where('id', $id)->value('spent_on');

        expect($raw)->toBe('2026-10-12');

        $this->putJson("/api/v1/expenses/{$id}", expensePayload(['spent_on' => '2026-10-13']))->assertOk();

        expect(DB::table('expenses')->where('id', $id)->value('spent_on'))->toBe('2026-10-13');
    });

    it('lists an expense on the last day of a range', function (): void {
        $this->postJson('/api/v1/expenses', expensePayload(['spent_on' => '2026-10-12']))->assertCreated();

        $this->getJson('/api/v1/expenses?from=2026-10-12&to=2026-10-12')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('counts an expense created through the api on the last day of a stats range', function (): void {
        $this->postJson('/api/v1/expenses', expensePayload(['spent_on' => '2026-10-12', 'amount_cents' => 700]))->assertCreated();

        $this->getJson('/api/v1/stats/summary?from=2026-10-12&to=2026-10-12')
            ->assertOk()
            ->assertJsonPath('data.total_cents', 700)
            ->assertJsonPath('data.count', 1);

        $this->getJson('/api/v1/stats/summary?from=2026-10-01&to=2026-10-12')
            ->assertOk()
            ->assertJsonPath('data.total_cents', 700);
    });
});
