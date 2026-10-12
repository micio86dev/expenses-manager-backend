<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Data\ExpenseData;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Expense;
use App\Repositories\Contracts\ExpenseRepository;

final class EloquentExpenseRepository implements ExpenseRepository
{
    public function get(string $id): Expense
    {
        return Expense::query()->find($id) ?? throw ResourceNotFoundException::for('expense');
    }

    public function categoryIdOf(string $id): ?string
    {
        $categoryId = Expense::query()->whereKey($id)->value('category_id');

        return is_string($categoryId) ? $categoryId : null;
    }

    public function create(ExpenseData $data): Expense
    {
        return Expense::query()->create($this->attributes($data));
    }

    public function update(Expense $expense, ExpenseData $data): Expense
    {
        $expense->fill($this->attributes($data))->save();

        return $expense;
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(ExpenseData $data): array
    {
        return [
            'description' => $data->description,
            'amount_cents' => $data->price->amountCents,
            'currency' => $data->price->currency,
            'category_id' => $data->categoryId,
            'spent_on' => $data->spentOn,
        ];
    }
}
