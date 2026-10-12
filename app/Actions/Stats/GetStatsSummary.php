<?php

declare(strict_types=1);

namespace App\Actions\Stats;

use App\Data\Stats\StatsQuery;
use App\Data\Stats\StatsSummary;
use App\Repositories\Contracts\StatsRepository;
use App\Support\Stats\StatsMath;

final readonly class GetStatsSummary
{
    public function __construct(
        private StatsRepository $stats,
        private GetCategoryStats $categories,
    ) {}

    public function __invoke(StatsQuery $query): StatsSummary
    {
        $current = $this->stats->totals($query);
        $previous = $this->stats->totals($query->previous());

        return new StatsSummary(
            currency: $query->currency,
            totalCents: $current->totalCents,
            count: $current->count,
            dailyAverageCents: StatsMath::dailyAverage($current->totalCents, $query->range->days()),
            previousTotalCents: $previous->totalCents,
            deltaPercent: StatsMath::deltaPercent($current->totalCents, $previous->totalCents),
            topCategory: ($this->categories)($query)[0] ?? null,
            biggestExpense: $this->stats->biggestExpense($query),
        );
    }
}
