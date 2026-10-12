<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Expense;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\StatsDataset;

/**
 * @return list<string> the SQL of every query the request ran
 */
function queriesOf(string $url): array
{
    $sql = [];
    DB::listen(static function ($query) use (&$sql): void {
        $sql[] = $query->sql;
    });

    test()->getJson($url)->assertOk();

    return $sql;
}

beforeEach(function (): void {
    $this->data = StatsDataset::seed();
    Sanctum::actingAs($this->data->alice);
});

$urls = [
    'summary' => ['/api/v1/stats/summary?from=2026-01-01&to=2026-12-31', 5],
    'by-category' => ['/api/v1/stats/by-category?from=2026-01-01&to=2026-12-31', 2],
    'timeseries' => ['/api/v1/stats/timeseries?from=2026-01-01&to=2026-12-31&granularity=week', 1],
];

it('runs a constant number of queries however much data there is', function (string $url, int $queries): void {
    $small = count(queriesOf($url));

    foreach (range(1, 30) as $index) {
        $category = Category::factory()->ownedBy($this->data->alice)->create();
        Expense::factory()
            ->count(7)
            ->ownedBy($this->data->alice)
            ->inCategory($category)
            ->on(sprintf('2026-%02d-%02d', ($index % 12) + 1, ($index % 28) + 1))
            ->create();
    }

    // The writes above moved the owner to a new cache version, so this is a real, uncached read.
    $large = count(queriesOf($url));

    expect($small)->toBe($queries)
        ->and($large)->toBe($queries);
})->with($urls);

it('selects only the columns it needs', function (string $url): void {
    foreach (queriesOf($url) as $sql) {
        expect($sql)->not->toMatch('/select\s+\*|\.\*/i');
    }
})->with(array_map(static fn (array $entry): array => [$entry[0]], $urls));

it('aggregates in the database instead of loading every expense', function (): void {
    $sql = strtolower(implode("\n", queriesOf('/api/v1/stats/timeseries?from=2026-01-01&to=2026-12-31')));

    expect($sql)->toContain('sum(amount_cents)')->toContain('count(*)')->toContain('group by "spent_on"');
});

it('has the indexes the statistics queries rely on', function (): void {
    $indexes = collect(Schema::getIndexes('expenses'))->pluck('columns')->map(fn (array $columns): string => implode(',', $columns));

    expect($indexes->all())->toContain('owner_id,spent_on')->toContain('owner_id,category_id,spent_on');
});

it('searches the owner and date index instead of scanning the table', function (): void {
    $plan = DB::select(
        'EXPLAIN QUERY PLAN select spent_on, sum(amount_cents), count(*) from expenses where owner_id = ? and spent_on between ? and ? and currency = ? group by spent_on',
        [$this->data->alice->id, '2026-10-01', '2026-10-31', 'EUR'],
    );
    $details = collect($plan)->pluck('detail')->implode(' ');

    expect($details)->toContain('SEARCH expenses USING')->toContain('owner_id=?')->not->toContain('SCAN expenses');
})->skip(fn (): bool => DB::getDriverName() !== 'sqlite', 'The query plan syntax is SQLite specific.');

it('fails loudly on lazy loading outside production', function (): void {
    // Lazy loading is only guarded for models hydrated as part of a collection.
    $expense = Expense::query()->get()->firstOrFail();

    expect(fn () => $expense->category)->toThrow(LazyLoadingViolationException::class);
});

it('reads relations through eager loading only', function (): void {
    $expense = Expense::query()->with('category')->get()->firstOrFail();

    expect($expense->category)->toBeInstanceOf(Category::class);
});
