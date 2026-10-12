<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\QueryException;
use Laravel\Sanctum\Sanctum;

it('links an expense to its category and back', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $category = Category::factory()->ownedBy($user)->create();
    $expense = Expense::factory()->ownedBy($user)->inCategory($category)->create();

    expect($expense->category()->firstOrFail()->is($category))->toBeTrue()
        ->and($category->expenses()->count())->toBe(1);
});

it('refuses to remove a category that expenses still reference, even bypassing the action', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $category = Category::factory()->ownedBy($user)->create();
    Expense::factory()->ownedBy($user)->inCategory($category)->create();

    expect(fn () => $category->delete())->toThrow(QueryException::class);
});
