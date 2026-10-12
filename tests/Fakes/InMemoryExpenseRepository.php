<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Data\ExpenseData;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Expense;
use App\Repositories\Contracts\ExpenseRepository;
use Illuminate\Support\Str;

final class InMemoryExpenseRepository implements ExpenseRepository
{
    /** @var array<string, Expense> */
    public array $expenses = [];

    public function get(string $id): Expense
    {
        return $this->expenses[$id] ?? throw ResourceNotFoundException::for('expense');
    }

    public function categoryIdOf(string $id): ?string
    {
        return ($this->expenses[$id] ?? null)?->category_id;
    }

    public function create(ExpenseData $data): Expense
    {
        $expense = new Expense;
        $expense->forceFill(['id' => (string) Str::uuid()]);

        return $this->expenses[$expense->id] = $this->fill($expense, $data);
    }

    public function update(Expense $expense, ExpenseData $data): Expense
    {
        return $this->fill($expense, $data);
    }

    public function delete(Expense $expense): void
    {
        unset($this->expenses[$expense->id]);
    }

    private function fill(Expense $expense, ExpenseData $data): Expense
    {
        return $expense->forceFill([
            'description' => $data->description,
            'amount_cents' => $data->price->amountCents,
            'currency' => $data->price->currency,
            'category_id' => $data->categoryId,
            'spent_on' => $data->spentOn,
        ]);
    }
}
