<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Data\Stats\CategoryTotal;
use App\Data\Stats\DayTotal;
use App\Data\Stats\StatsQuery;
use App\Data\Stats\Totals;
use App\Models\Category;
use App\Models\Expense;
use App\Repositories\Contracts\StatsRepository;
use App\Support\DateRange;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aggregates in SQL with the standard SUM, COUNT and GROUP BY over the plain `spent_on` column
 * (served by the `(owner_id, spent_on)` index). No dialect functions: week and month buckets
 * are built in PHP from the per-day rows. The owner scope applies to every query.
 */
final class EloquentStatsRepository implements StatsRepository
{
    private const string AGGREGATES = 'SUM(amount_cents) AS total_cents, COUNT(*) AS expense_count';

    public function totals(StatsQuery $query): Totals
    {
        $row = $this->expenses($query)->selectRaw(self::AGGREGATES)->toBase()->first();

        return new Totals(
            totalCents: $this->int($row->total_cents ?? 0),
            count: $this->int($row->expense_count ?? 0),
        );
    }

    public function byCategory(StatsQuery $query): array
    {
        $rows = $this->expenses($query)
            ->select('category_id')
            ->selectRaw(self::AGGREGATES)
            ->groupBy('category_id')
            ->toBase()
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $categories = Category::query()
            ->whereIn('id', $rows->pluck('category_id')->all())
            ->get(['id', 'name', 'is_system', 'translation_key'])
            ->keyBy('id');

        $totals = [];

        foreach ($rows as $row) {
            $categoryId = $this->text($row->category_id);

            $totals[] = new CategoryTotal(
                categoryId: $categoryId,
                name: $categories->get($categoryId)?->displayName() ?? '',
                totalCents: $this->int($row->total_cents),
                count: $this->int($row->expense_count),
            );
        }

        return $totals;
    }

    public function dailyTotals(StatsQuery $query): array
    {
        $rows = $this->expenses($query)
            ->select('spent_on')
            ->selectRaw(self::AGGREGATES)
            ->groupBy('spent_on')
            ->orderBy('spent_on')
            ->toBase()
            ->get();

        $days = [];

        foreach ($rows as $row) {
            $days[] = new DayTotal(
                day: DateRange::day(substr($this->text($row->spent_on), 0, 10)),
                totalCents: $this->int($row->total_cents),
                count: $this->int($row->expense_count),
            );
        }

        return $days;
    }

    public function biggestExpense(StatsQuery $query): ?Expense
    {
        return $this->expenses($query)
            ->orderByDesc('amount_cents')
            ->orderByDesc('spent_on')
            ->orderByDesc('id')
            ->first(['id', 'description', 'amount_cents', 'currency', 'category_id', 'spent_on', 'created_at', 'updated_at']);
    }

    /**
     * @return Builder<Expense>
     */
    private function expenses(StatsQuery $query): Builder
    {
        return Expense::query()
            ->spentWithin($query->range)
            ->where('currency', $query->currency);
    }

    private function text(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
