<?php

declare(strict_types=1);

namespace App\Actions\Stats;

use App\Data\Stats\CategoryStat;
use App\Data\Stats\CategoryTotal;
use App\Data\Stats\StatsQuery;
use App\Repositories\Contracts\StatsRepository;
use App\Support\Stats\StatsMath;

final readonly class GetCategoryStats
{
    public function __construct(private StatsRepository $stats) {}

    /**
     * Largest total first; equal totals are ordered by name, then id, so the order is stable.
     *
     * @return list<CategoryStat>
     */
    public function __invoke(StatsQuery $query): array
    {
        $totals = $this->stats->byCategory($query);
        $grand = array_sum(array_map(static fn (CategoryTotal $total): int => $total->totalCents, $totals));

        usort($totals, static fn (CategoryTotal $a, CategoryTotal $b): int => [$b->totalCents, $a->name, $a->categoryId] <=> [$a->totalCents, $b->name, $b->categoryId]);

        return array_map(
            static fn (CategoryTotal $total): CategoryStat => new CategoryStat(
                categoryId: $total->categoryId,
                name: $total->name,
                totalCents: $total->totalCents,
                count: $total->count,
                sharePercent: StatsMath::sharePercent($total->totalCents, $grand),
            ),
            $totals,
        );
    }
}
