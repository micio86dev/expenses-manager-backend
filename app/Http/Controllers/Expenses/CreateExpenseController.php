<?php

declare(strict_types=1);

namespace App\Http\Controllers\Expenses;

use App\Actions\Expenses\CreateExpense;
use App\Http\Requests\Expenses\SaveExpenseRequest;
use App\Http\Resources\ExpenseResource;

final readonly class CreateExpenseController
{
    public function __invoke(SaveExpenseRequest $request, CreateExpense $create): ExpenseResource
    {
        // A freshly created model is answered with 201 (and a Location header) by the resource.
        return new ExpenseResource($create($request->toData()));
    }
}
