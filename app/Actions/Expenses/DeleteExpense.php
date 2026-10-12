<?php

declare(strict_types=1);

namespace App\Actions\Expenses;

use App\Repositories\Contracts\ExpenseRepository;

final readonly class DeleteExpense
{
    public function __construct(private ExpenseRepository $expenses) {}

    public function __invoke(string $id): void
    {
        $this->expenses->delete($this->expenses->get($id));
    }
}
