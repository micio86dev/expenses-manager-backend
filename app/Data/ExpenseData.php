<?php

declare(strict_types=1);

namespace App\Data;

use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * The editable fields of an expense.
 */
final readonly class ExpenseData
{
    public function __construct(
        public string $description,
        public Money $price,
        public string $categoryId,
        public CarbonImmutable $spentOn,
    ) {}
}
