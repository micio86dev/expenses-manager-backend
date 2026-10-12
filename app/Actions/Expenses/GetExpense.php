<?php

declare(strict_types=1);

namespace App\Actions\Expenses;

use App\Models\Expense;
use App\Repositories\Contracts\ExpenseRepository;

final readonly class GetExpense
{
    public function __construct(private ExpenseRepository $expenses) {}

    public function __invoke(string $id): Expense
    {
        return $this->expenses->get($id);
    }
}
