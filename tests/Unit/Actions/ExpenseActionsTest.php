<?php

declare(strict_types=1);

use App\Actions\Expenses\CreateExpense;
use App\Actions\Expenses\DeleteExpense;
use App\Actions\Expenses\GetExpense;
use App\Actions\Expenses\UpdateExpense;
use App\Data\ExpenseData;
use App\Exceptions\ResourceNotFoundException;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Tests\Fakes\InMemoryExpenseRepository;

beforeEach(function (): void {
    $this->expenses = new InMemoryExpenseRepository;
});

function expenseData(string $description = 'Lunch', int $cents = 1250): ExpenseData
{
    return new ExpenseData($description, new Money($cents), '0199c0a6-5b3b-7000-8000-000000000001', CarbonImmutable::parse('2026-10-12'));
}

it('creates, reads and updates an expense', function (): void {
    $created = (new CreateExpense($this->expenses))(expenseData());

    expect((new GetExpense($this->expenses))($created->id)->description)->toBe('Lunch');

    $updated = (new UpdateExpense($this->expenses))($created->id, expenseData('Dinner', 3000));

    expect($updated->description)->toBe('Dinner')->and($updated->amount_cents)->toBe(3000)->and($updated->id)->toBe($created->id);
});

it('deletes an expense', function (): void {
    $created = (new CreateExpense($this->expenses))(expenseData());

    (new DeleteExpense($this->expenses))($created->id);

    expect($this->expenses->expenses)->toBeEmpty();
});

it('reports a missing expense as not found', function (): void {
    expect(fn () => (new GetExpense($this->expenses))('missing'))->toThrow(ResourceNotFoundException::class)
        ->and(fn () => (new UpdateExpense($this->expenses))('missing', expenseData()))->toThrow(ResourceNotFoundException::class)
        ->and(fn () => (new DeleteExpense($this->expenses))('missing'))->toThrow(ResourceNotFoundException::class);
});
