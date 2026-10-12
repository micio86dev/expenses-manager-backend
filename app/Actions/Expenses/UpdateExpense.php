<?php

declare(strict_types=1);

namespace App\Actions\Expenses;

use App\Data\ExpenseData;
use App\Models\Expense;
use App\Repositories\Contracts\ExpenseRepository;

final readonly class UpdateExpense
{
    public function __construct(private ExpenseRepository $expenses) {}

    public function __invoke(string $id, ExpenseData $data): Expense
    {
        return $this->expenses->update($this->expenses->get($id), $data);
    }
}
