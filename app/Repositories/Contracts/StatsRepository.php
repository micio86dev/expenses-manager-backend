<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\Stats\CategoryTotal;
use App\Data\Stats\DayTotal;
use App\Data\Stats\StatsQuery;
use App\Data\Stats\Totals;
use App\Models\Expense;

/**
 * Aggregates of the current owner's expenses in one currency. Every method answers with
 * a fixed number of queries, whatever the data volume.
 */
interface StatsRepository
{
    public function totals(StatsQuery $query): Totals;

    /**
     * One entry per category with spending in the range, in no particular order.
     *
     * @return list<CategoryTotal>
     */
    public function byCategory(StatsQuery $query): array;

    /**
     * One entry per day with spending in the range, oldest first. Days without spending are absent.
     *
     * @return list<DayTotal>
     */
    public function dailyTotals(StatsQuery $query): array;

    /**
     * The largest single expense of the range (latest, then highest id, on ties), or null.
     */
    public function biggestExpense(StatsQuery $query): ?Expense;

    /**
     * The largest expenses of the range: amount, then date, then id, all descending.
     *
     * @param  positive-int  $limit
     * @return list<Expense>
     */
    public function topExpenses(StatsQuery $query, int $limit): array;
}
