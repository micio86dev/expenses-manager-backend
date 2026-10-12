<?php

declare(strict_types=1);

namespace App\Actions\Expenses;

use App\Data\ExpenseData;
use App\Models\Expense;
use App\Repositories\Contracts\ExpenseRepository;

final readonly class CreateExpense
{
    public function __construct(private ExpenseRepository $expenses) {}

    public function __invoke(ExpenseData $data): Expense
    {
        return $this->expenses->create($data);
    }
}
