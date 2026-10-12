<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\ExpenseData;
use App\Data\ExpenseFilter;
use App\Data\ExpensePage;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Expense;

/**
 * Expenses of the current owner.
 */
interface ExpenseRepository
{
    /**
     * Expenses matching the filter, newest first (expense date, then creation time).
     */
    public function page(ExpenseFilter $filter): ExpensePage;

    /**
     * @throws ResourceNotFoundException
     */
    public function get(string $id): Expense;

    /**
     * The category an expense currently uses, or null when the expense is not visible.
     */
    public function categoryIdOf(string $id): ?string;

    public function create(ExpenseData $data): Expense;

    public function update(Expense $expense, ExpenseData $data): Expense;

    public function delete(Expense $expense): void;
}
