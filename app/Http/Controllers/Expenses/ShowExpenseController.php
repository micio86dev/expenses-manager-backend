<?php

declare(strict_types=1);

namespace App\Http\Controllers\Expenses;

use App\Actions\Expenses\GetExpense;
use App\Http\Resources\ExpenseResource;

final readonly class ShowExpenseController
{
    public function __invoke(string $expense, GetExpense $get): ExpenseResource
    {
        return new ExpenseResource($get($expense));
    }
}
