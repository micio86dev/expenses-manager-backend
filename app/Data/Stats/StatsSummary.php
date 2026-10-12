<?php

declare(strict_types=1);

namespace App\Data\Stats;

use App\Models\Expense;

final readonly class StatsSummary
{
    public function __construct(
        public string $currency,
        public int $totalCents,
        public int $count,
        public int $dailyAverageCents,
        public int $previousTotalCents,
        public ?float $deltaPercent,
        public ?CategoryStat $topCategory,
        public ?Expense $biggestExpense,
    ) {}
}
