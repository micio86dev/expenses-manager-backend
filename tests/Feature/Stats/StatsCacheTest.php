<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Expense;
use App\Support\Stats\StatsVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\StatsDataset;

const CACHED_SUMMARY = '/api/v1/stats/summary?from=2026-10-01&to=2026-10-31';

/**
 * @return array{0: mixed, 1: int} the decoded body and the number of queries the request ran
 */
function summaryWithQueryCount(string $url = CACHED_SUMMARY, array $headers = []): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $body = test()->getJson($url, $headers)->assertOk()->assertValidResponse(200)->json();
    $queries = count(DB::getQueryLog());

    DB::disableQueryLog();

    return [$body, $queries];
}

beforeEach(function (): void {
    $this->data = StatsDataset::seed();
    Sanctum::actingAs($this->data->alice);
});

it('serves a repeated read from the cache without touching the database', function (string $endpoint): void {
    $url = "/api/v1/stats/{$endpoint}?from=2026-10-01&to=2026-10-31";

    [$first, $firstQueries] = summaryWithQueryCount($url);
    [$second, $secondQueries] = summaryWithQueryCount($url);

    expect($firstQueries)->toBeGreaterThan(0)
        ->and($secondQueries)->toBe(0)
        ->and($second)->toBe($first);
})->with(['summary', 'by-category', 'timeseries']);

it('caches each distinct query separately', function (): void {
    summaryWithQueryCount();
    [$usd, $queries] = summaryWithQueryCount(CACHED_SUMMARY.'&currency=USD');
    [$other, $otherQueries] = summaryWithQueryCount('/api/v1/stats/summary?from=2026-09-01&to=2026-09-30');

    expect($queries)->toBeGreaterThan(0)
        ->and($usd['data']['total_cents'])->toBe(9999)
        ->and($otherQueries)->toBeGreaterThan(0)
        ->and($other['data']['total_cents'])->toBe(5000);
});

it('expires cached statistics after an hour', function (): void {
    summaryWithQueryCount();

    $this->travel(59)->minutes();
    expect(summaryWithQueryCount()[1])->toBe(0);

    $this->travel(2)->minutes();
    expect(summaryWithQueryCount()[1])->toBeGreaterThan(0);
});

it('keeps locales apart because category names are translated', function (): void {
    $system = Category::factory()->system('housing')->create();
    Expense::factory()->ownedBy($this->data->alice)->inCategory($system)->on('2026-10-20')->create(['amount_cents' => 90000]);

    $italian = summaryWithQueryCount(CACHED_SUMMARY, ['Accept-Language' => 'it'])[0];
    $english = summaryWithQueryCount(CACHED_SUMMARY, ['Accept-Language' => 'en'])[0];
    $italianAgain = summaryWithQueryCount(CACHED_SUMMARY, ['Accept-Language' => 'it']);

    expect($italian['data']['top_category']['name'])->toBe('Casa')
        ->and($english['data']['top_category']['name'])->toBe('Housing')
        ->and($italianAgain[0])->toBe($italian)
        ->and($italianAgain[1])->toBe(0);
});

describe('invalidation', function (): void {
    it('is triggered by creating, updating and deleting an expense through the API', function (): void {
        $before = summaryWithQueryCount()[0]['data']['total_cents'];

        $created = $this->postJson('/api/v1/expenses', [
            'description' => 'Coffee',
            'amount_cents' => 250,
            'currency' => 'EUR',
            'category_id' => $this->data->food->id,
            'spent_on' => '2026-10-20',
        ])->assertCreated()->json('data.id');

        expect(summaryWithQueryCount()[0]['data']['total_cents'])->toBe($before + 250);

        $this->putJson("/api/v1/expenses/{$created}", [
            'description' => 'Coffee',
            'amount_cents' => 400,
            'currency' => 'EUR',
            'category_id' => $this->data->food->id,
            'spent_on' => '2026-10-20',
        ])->assertOk();

        expect(summaryWithQueryCount()[0]['data']['total_cents'])->toBe($before + 400);

        $this->deleteJson("/api/v1/expenses/{$created}")->assertNoContent();

        expect(summaryWithQueryCount()[0]['data']['total_cents'])->toBe($before);
    });

    it('is triggered by renaming a category, which changes the statistics names', function (): void {
        $url = '/api/v1/stats/by-category?from=2026-10-01&to=2026-10-31';
        $names = fn (): array => collect(summaryWithQueryCount($url)[0]['data'])->pluck('name')->all();

        expect($names())->toContain('Food');

        $this->putJson("/api/v1/categories/{$this->data->food->id}", [
            'name' => 'Groceries',
            'color' => '#112233',
            'icon' => $this->data->food->icon->value,
        ])->assertOk();

        expect($names())->toContain('Groceries')->not->toContain('Food');
    });

    it('is triggered by writes made outside the API as well', function (): void {
        $before = summaryWithQueryCount()[0]['data']['count'];

        Expense::factory()->ownedBy($this->data->alice)->inCategory($this->data->food)->on('2026-10-02')->create();

        expect(summaryWithQueryCount()[0]['data']['count'])->toBe($before + 1);
    });

    it('does not rebuild the cache of anyone else', function (): void {
        summaryWithQueryCount();

        Expense::factory()->ownedBy($this->data->bob)->on('2026-10-02')->create();

        expect(summaryWithQueryCount()[1])->toBe(0);
    });
});

it('never shares cache entries between owners', function (): void {
    $alice = summaryWithQueryCount()[0]['data'];

    Sanctum::actingAs($this->data->bob);
    [$bob, $bobQueries] = summaryWithQueryCount();

    expect($bobQueries)->toBeGreaterThan(0)
        ->and($alice['total_cents'])->toBe(12500)
        ->and($bob['data']['total_cents'])->toBe(88888)
        ->and($bob['data']['top_category']['name'])->toBe('Bobs');

    Sanctum::actingAs($this->data->alice);

    expect(summaryWithQueryCount()[0]['data']['total_cents'])->toBe(12500);
});

it('does not cache failures', function (): void {
    $this->getJson('/api/v1/stats/summary?from=2026-10-31&to=2026-10-01')->assertUnprocessable();

    $this->getJson('/api/v1/stats/summary?from=2026-10-01&to=2026-10-31')->assertOk();
});

it('invalidates on every cache driver, without cache tags', function (string $driver): void {
    config([
        'cache.default' => $driver,
        'cache.stores.file.path' => storage_path('framework/testing/stats-cache-'.uniqid()),
    ]);
    app('cache')->forgetDriver();
    app()->forgetInstance('cache.store');
    Cache::flush();

    $before = summaryWithQueryCount()[0]['data']['total_cents'];

    Expense::factory()->ownedBy($this->data->alice)->inCategory($this->data->food)->on('2026-10-02')->create(['amount_cents' => 700]);

    expect(summaryWithQueryCount()[0]['data']['total_cents'])->toBe($before + 700);

    Cache::flush();
})->with(['array', 'file', 'database']);

it('bumps the version only once the writing transaction has committed', function (): void {
    $versions = app(StatsVersion::class);
    $before = $versions->current($this->data->alice->id);
    $inside = null;

    DB::transaction(function () use ($versions, &$inside): void {
        Expense::factory()->ownedBy($this->data->alice)->inCategory($this->data->food)->on('2026-10-02')->create();
        $inside = $versions->current($this->data->alice->id);
    });

    expect($inside)->toBe($before)
        ->and($versions->current($this->data->alice->id))->not->toBe($before);
});

it('leaves system categories out of invalidation because they belong to nobody', function (): void {
    $versions = app(StatsVersion::class);
    $before = $versions->current($this->data->alice->id);

    Category::factory()->system('other')->create();

    expect($versions->current($this->data->alice->id))->toBe($before);
});
