<?php

declare(strict_types=1);

namespace App\Http\Controllers\Expenses;

use App\Actions\Expenses\ListExpenses;
use App\Http\Requests\Expenses\ListExpensesRequest;
use App\Http\Resources\ExpenseCollection;

final readonly class ListExpensesController
{
    public function __invoke(ListExpensesRequest $request, ListExpenses $list): ExpenseCollection
    {
        return new ExpenseCollection($list($request->toFilter()));
    }
}
