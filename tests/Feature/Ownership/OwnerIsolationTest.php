<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Owner\CurrentOwner;
use App\Support\Owner\MissingOwnerException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\Fixtures\OwnedNote;

beforeEach(function (): void {
    Schema::create('owned_notes', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->uuid('owner_id')->index();
        $table->string('title');
        $table->timestamps();
    });

    $this->alice = User::factory()->create();
    $this->bob = User::factory()->create();
});

it('stamps new rows with the current owner', function (): void {
    Sanctum::actingAs($this->alice);

    $note = OwnedNote::query()->create(['title' => 'alice note']);

    expect($note->owner_id)->toBe($this->alice->id);
});

it('keeps one owner from reading the rows of another', function (): void {
    Sanctum::actingAs($this->alice);
    $alicesNote = OwnedNote::query()->create(['title' => 'alice note']);

    Sanctum::actingAs($this->bob);
    $bobsNote = OwnedNote::query()->create(['title' => 'bob note']);

    expect(OwnedNote::query()->pluck('title')->all())->toBe(['bob note'])
        ->and(OwnedNote::query()->count())->toBe(1)
        ->and(OwnedNote::query()->find($alicesNote->id))->toBeNull()
        ->and(OwnedNote::query()->find($bobsNote->id))->not->toBeNull();

    Sanctum::actingAs($this->alice);

    expect(OwnedNote::query()->pluck('title')->all())->toBe(['alice note'])
        ->and(OwnedNote::query()->find($bobsNote->id))->toBeNull();
});

it('keeps one owner from updating or deleting the rows of another', function (): void {
    Sanctum::actingAs($this->alice);
    $note = OwnedNote::query()->create(['title' => 'alice note']);

    Sanctum::actingAs($this->bob);

    expect(OwnedNote::query()->whereKey($note->id)->update(['title' => 'hijacked']))->toBe(0)
        ->and(OwnedNote::query()->whereKey($note->id)->delete())->toBe(0);

    Sanctum::actingAs($this->alice);
    expect(OwnedNote::query()->findOrFail($note->id)->title)->toBe('alice note');
});

it('qualifies the owner column so joins stay unambiguous', function (): void {
    Sanctum::actingAs($this->alice);

    expect(OwnedNote::query()->toSql())->toContain('"owned_notes"."owner_id" = ?');
});

it('matches nothing when nobody is authenticated', function (): void {
    Sanctum::actingAs($this->alice);
    OwnedNote::query()->create(['title' => 'alice note']);

    app('auth')->guard('sanctum')->forgetUser();

    expect(OwnedNote::query()->count())->toBe(0);
});

it('refuses to create rows without an owner', function (): void {
    expect(fn () => OwnedNote::query()->create(['title' => 'orphan']))
        ->toThrow(MissingOwnerException::class);
});

it('lets trusted code bypass the scope explicitly', function (): void {
    Sanctum::actingAs($this->alice);
    OwnedNote::query()->create(['title' => 'alice note']);

    Sanctum::actingAs($this->bob);

    expect(OwnedNote::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('follows whatever the CurrentOwner binding says, e.g. a tenant', function (): void {
    $tenant = new class implements CurrentOwner
    {
        public function id(): string
        {
            return '11111111-1111-4111-8111-111111111111';
        }
    };
    app()->instance(CurrentOwner::class, $tenant);

    $note = OwnedNote::query()->create(['title' => 'tenant note']);

    expect($note->owner_id)->toBe($tenant->id())
        ->and(OwnedNote::query()->count())->toBe(1);
});

it('keeps an owner assigned explicitly, as seeders and imports do', function (): void {
    $note = OwnedNote::query()->withoutGlobalScopes()->make(['title' => 'imported']);
    $note->owner_id = $this->bob->id;
    $note->save();

    expect($note->refresh()->owner_id)->toBe($this->bob->id);

    Sanctum::actingAs($this->bob);
    expect(OwnedNote::query()->count())->toBe(1);
});
