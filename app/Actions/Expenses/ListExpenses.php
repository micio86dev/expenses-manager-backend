<?php

declare(strict_types=1);

namespace App\Actions\Expenses;

use App\Data\ExpenseFilter;
use App\Data\ExpensePage;
use App\Repositories\Contracts\ExpenseRepository;

final readonly class ListExpenses
{
    public function __construct(private ExpenseRepository $expenses) {}

    public function __invoke(ExpenseFilter $filter): ExpensePage
    {
        return $this->expenses->page($filter);
    }
}
