<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Data\Stats\CategoryTotal;
use App\Data\Stats\DayTotal;
use App\Data\Stats\StatsQuery;
use App\Data\Stats\Totals;
use App\Models\Expense;
use App\Repositories\Contracts\StatsRepository;
use App\Support\DateRange;
use Carbon\CarbonImmutable;

/**
 * Aggregates a plain list of spending rows the way the database implementation does.
 */
final class InMemoryStatsRepository implements StatsRepository
{
    /** @var list<array{id: string, day: string, cents: int, currency: string, categoryId: string, category: string}> */
    private array $rows = [];

    public function spend(string $day, int $cents, string $category = 'Food', string $currency = 'EUR'): self
    {
        $this->rows[] = [
            'id' => 'expense-'.(count($this->rows) + 1),
            'day' => $day,
            'cents' => $cents,
            'currency' => $currency,
            'categoryId' => 'category-'.$category,
            'category' => $category,
        ];

        return $this;
    }

    public function totals(StatsQuery $query): Totals
    {
        $rows = $this->within($query);

        return new Totals(array_sum(array_column($rows, 'cents')), count($rows));
    }

    public function byCategory(StatsQuery $query): array
    {
        $groups = [];

        foreach ($this->within($query) as $row) {
            $group = $groups[$row['categoryId']] ?? ['name' => $row['category'], 'cents' => 0, 'count' => 0];
            $groups[$row['categoryId']] = ['name' => $group['name'], 'cents' => $group['cents'] + $row['cents'], 'count' => $group['count'] + 1];
        }

        $totals = [];

        foreach ($groups as $id => $group) {
            $totals[] = new CategoryTotal($id, $group['name'], $group['cents'], $group['count']);
        }

        return $totals;
    }

    public function dailyTotals(StatsQuery $query): array
    {
        $days = [];

        foreach ($this->within($query) as $row) {
            $day = $days[$row['day']] ?? ['cents' => 0, 'count' => 0];
            $days[$row['day']] = ['cents' => $day['cents'] + $row['cents'], 'count' => $day['count'] + 1];
        }

        ksort($days);

        $totals = [];

        foreach ($days as $date => $day) {
            $totals[] = new DayTotal(DateRange::day((string) $date), $day['cents'], $day['count']);
        }

        return $totals;
    }

    public function biggestExpense(StatsQuery $query): ?Expense
    {
        $rows = $this->within($query);

        usort($rows, static fn (array $a, array $b): int => [$b['cents'], $b['day'], $b['id']] <=> [$a['cents'], $a['day'], $a['id']]);

        if ($rows === []) {
            return null;
        }

        return (new Expense)->forceFill([
            'id' => $rows[0]['id'],
            'amount_cents' => $rows[0]['cents'],
            'currency' => $rows[0]['currency'],
            'category_id' => $rows[0]['categoryId'],
            'spent_on' => CarbonImmutable::parse($rows[0]['day']),
        ]);
    }

    public function topExpenses(StatsQuery $query, int $limit): array
    {
        $rows = $this->within($query);

        usort($rows, static fn (array $a, array $b): int => [$b['cents'], $b['day'], $b['id']] <=> [$a['cents'], $a['day'], $a['id']]);

        return array_map(
            static fn (array $row): Expense => (new Expense)->forceFill([
                'id' => $row['id'],
                'amount_cents' => $row['cents'],
                'currency' => $row['currency'],
                'category_id' => $row['categoryId'],
                'spent_on' => CarbonImmutable::parse($row['day']),
            ]),
            array_slice($rows, 0, $limit),
        );
    }

    /**
     * @return list<array{id: string, day: string, cents: int, currency: string, categoryId: string, category: string}>
     */
    private function within(StatsQuery $query): array
    {
        return array_values(array_filter(
            $this->rows,
            static fn (array $row): bool => $row['currency'] === $query->currency
                && $query->range->contains(DateRange::day($row['day'])),
        ));
    }
}
