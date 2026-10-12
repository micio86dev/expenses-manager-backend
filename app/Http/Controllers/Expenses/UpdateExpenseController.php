<?php

declare(strict_types=1);

namespace App\Http\Controllers\Expenses;

use App\Actions\Expenses\UpdateExpense;
use App\Http\Requests\Expenses\SaveExpenseRequest;
use App\Http\Resources\ExpenseResource;

final readonly class UpdateExpenseController
{
    public function __invoke(SaveExpenseRequest $request, string $expense, UpdateExpense $update): ExpenseResource
    {
        return new ExpenseResource($update($expense, $request->toData()));
    }
}
