<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Expense;

/**
 * One page of expenses with its pagination figures.
 */
final readonly class ExpensePage
{
    /**
     * @param  list<Expense>  $items
     */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $perPage,
        public int $total,
        public int $lastPage,
    ) {}
}
