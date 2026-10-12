<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->alice = User::factory()->create();
    $this->bob = User::factory()->create();
    $this->food = Category::factory()->ownedBy($this->alice)->create(['name' => 'Food']);
    $this->fun = Category::factory()->ownedBy($this->alice)->create(['name' => 'Fun']);
    Sanctum::actingAs($this->alice);
});

function listUrl(array $query = []): string
{
    return '/api/v1/expenses?'.http_build_query([...['from' => '2026-10-01', 'to' => '2026-10-31'], ...$query]);
}

function spend(string $date, array $attributes = []): Expense
{
    return Expense::factory()->ownedBy(test()->alice)->inCategory(test()->food)->on($date)->create($attributes);
}

describe('filters', function (): void {
    it('requires a valid date range', function (array $query, string $field): void {
        $this->getJson('/api/v1/expenses?'.http_build_query($query))
            ->assertUnprocessable()
            ->assertValidResponse(422)
            ->assertJsonValidationErrors($field);
    })->with([
        'no range' => [[], 'from'],
        'missing to' => [['from' => '2026-10-01'], 'to'],
        'missing from' => [['to' => '2026-10-31'], 'from'],
        'malformed from' => [['from' => 'last week', 'to' => '2026-10-31'], 'from'],
        'impossible to' => [['from' => '2026-10-01', 'to' => '2026-02-30'], 'to'],
        'to before from' => [['from' => '2026-10-31', 'to' => '2026-10-01'], 'to'],
    ]);

    it('includes both ends of the range and nothing outside it', function (): void {
        spend('2026-09-30');
        $first = spend('2026-10-01');
        $last = spend('2026-10-31');
        spend('2026-11-01');

        $ids = collect($this->getJson(listUrl())->assertOk()->assertValidResponse(200)->json('data'))->pluck('id');

        expect($ids->all())->toEqualCanonicalizing([$first->id, $last->id]);
    });

    it('accepts a single-day range', function (): void {
        $day = spend('2026-10-12');
        spend('2026-10-13');

        $this->getJson(listUrl(['from' => '2026-10-12', 'to' => '2026-10-12']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $day->id);
    });

    it('filters by category', function (): void {
        $food = spend('2026-10-05');
        Expense::factory()->ownedBy($this->alice)->inCategory($this->fun)->on('2026-10-06')->create();

        $this->getJson(listUrl(['category_id' => $this->food->id]))
            ->assertOk()
            ->assertValidResponse(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $food->id);
    });

    it('searches the description case-insensitively', function (): void {
        $match = spend('2026-10-05', ['description' => 'Whole MILK 2L']);
        spend('2026-10-06', ['description' => 'Bread']);

        foreach (['milk', 'MILK', 'Milk 2'] as $term) {
            $this->getJson(listUrl(['q' => $term]))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $match->id);
        }
    });

    it('combines the filters', function (): void {
        spend('2026-10-05', ['description' => 'Milk']);
        Expense::factory()->ownedBy($this->alice)->inCategory($this->fun)->on('2026-10-05')->create(['description' => 'Milk shake']);
        spend('2026-09-05', ['description' => 'Milk']);

        $this->getJson(listUrl(['category_id' => $this->fun->id, 'q' => 'milk']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Milk shake');
    });

    it('rejects malformed optional filters', function (array $query, string $field): void {
        $this->getJson(listUrl($query))->assertUnprocessable()->assertValidResponse(422)->assertJsonValidationErrors($field);
    })->with([
        'category not a uuid' => [['category_id' => 'food'], 'category_id'],
        'empty search' => [['q' => ''], 'q'],
        'search too long' => [['q' => str_repeat('a', 101)], 'q'],
        'page zero' => [['page' => 0], 'page'],
        'page not a number' => [['page' => 'two'], 'page'],
        'page size zero' => [['per_page' => 0], 'per_page'],
        'page size over the cap' => [['per_page' => 101], 'per_page'],
    ]);
});

describe('ordering and pagination', function (): void {
    it('sorts by expense date then creation time, newest first', function (): void {
        $this->travelTo(now()->startOfSecond());
        $older = spend('2026-10-01');
        $this->travel(1)->minute();
        $newerSameDay = spend('2026-10-10');
        $this->travel(1)->minute();
        $latestSameDay = spend('2026-10-10');

        $ids = collect($this->getJson(listUrl())->assertValidResponse(200)->json('data'))->pluck('id')->all();

        expect($ids)->toBe([$latestSameDay->id, $newerSameDay->id, $older->id]);
    });

    it('paginates with the documented meta, 50 per page by default', function (): void {
        Expense::factory()->count(55)->ownedBy($this->alice)->inCategory($this->food)->on('2026-10-10')->create();

        $this->getJson(listUrl())
            ->assertOk()
            ->assertValidResponse(200)
            ->assertJsonCount(50, 'data')
            ->assertExactJsonStructure(['data' => ['*' => ['id', 'description', 'amount_cents', 'currency', 'category_id', 'spent_on', 'created_at', 'updated_at']], 'meta' => ['current_page', 'per_page', 'total', 'last_page']])
            ->assertJsonPath('meta', ['current_page' => 1, 'per_page' => 50, 'total' => 55, 'last_page' => 2]);

        $this->getJson(listUrl(['page' => 2]))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    });

    it('honours per_page up to 100 and never repeats an item across pages', function (): void {
        Expense::factory()->count(7)->ownedBy($this->alice)->inCategory($this->food)->on('2026-10-10')->create();

        $seen = collect([1, 2, 3, 4])->flatMap(
            fn (int $page): array => $this->getJson(listUrl(['per_page' => 2, 'page' => $page]))->json('data'),
        )->pluck('id');

        expect($seen)->toHaveCount(7)->and($seen->unique())->toHaveCount(7);

        $this->getJson(listUrl(['per_page' => 100]))->assertOk()->assertJsonPath('meta.per_page', 100);
    });

    it('answers an empty last page for a page beyond the end', function (): void {
        spend('2026-10-10');

        $this->getJson(listUrl(['page' => 9]))
            ->assertOk()
            ->assertValidResponse(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta', ['current_page' => 9, 'per_page' => 50, 'total' => 1, 'last_page' => 1]);
    });

    it('reports a single empty page when nothing matches', function (): void {
        $this->getJson(listUrl())
            ->assertOk()
            ->assertValidResponse(200)
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta', ['current_page' => 1, 'per_page' => 50, 'total' => 0, 'last_page' => 1]);
    });
});

describe('isolation', function (): void {
    it('never lists the expenses of another owner', function (): void {
        $mine = spend('2026-10-10');
        $bobsCategory = Category::factory()->ownedBy($this->bob)->create();
        $bobs = Expense::factory()->ownedBy($this->bob)->inCategory($bobsCategory)->on('2026-10-10')->create();

        $ids = collect($this->getJson(listUrl())->json('data'))->pluck('id');

        expect($ids->all())->toBe([$mine->id])
            ->and($this->getJson(listUrl(['category_id' => $bobsCategory->id]))->json('meta.total'))->toBe(0)
            ->and($ids)->not->toContain($bobs->id);

        Sanctum::actingAs($this->bob);

        expect(collect($this->getJson(listUrl())->json('data'))->pluck('id')->all())->toBe([$bobs->id]);
    });
});

describe('performance', function (): void {
    function listQueryCount(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        test()->getJson(listUrl(['per_page' => 100]))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    it('runs a constant number of queries however many expenses it returns', function (): void {
        Expense::factory()->count(2)->ownedBy($this->alice)->inCategory($this->food)->on('2026-10-10')->create();
        $few = listQueryCount();

        Expense::factory()->count(60)->ownedBy($this->alice)->inCategory($this->fun)->on('2026-10-11')->create();
        $many = listQueryCount();

        // One COUNT and one SELECT; no per-row category lookups (preventLazyLoading would throw).
        expect($few)->toBe(2)->and($many)->toBe($few);
    });

    it('selects only the columns the response needs', function (): void {
        spend('2026-10-10');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(listUrl())->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        expect($queries->contains(fn (string $sql): bool => str_contains($sql, 'select "id", "description", "amount_cents", "currency", "category_id", "spent_on", "created_at", "updated_at" from "expenses"')))->toBeTrue();
    });
});
