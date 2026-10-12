<?php

declare(strict_types=1);

namespace App\Http\Controllers\Expenses;

use App\Actions\Expenses\DeleteExpense;
use Illuminate\Http\Response;

final readonly class DeleteExpenseController
{
    public function __invoke(string $expense, DeleteExpense $delete): Response
    {
        $delete($expense);

        return new Response(status: 204);
    }
}
