<?php

declare(strict_types=1);

namespace App\Actions\Stats;

use App\Data\Stats\StatsQuery;
use App\Models\Expense;
use App\Repositories\Contracts\StatsRepository;

final readonly class GetTopExpenses
{
    public function __construct(private StatsRepository $stats) {}

    /**
     * @param  positive-int  $limit
     * @return list<Expense>
     */
    public function __invoke(StatsQuery $query, int $limit): array
    {
        return $this->stats->topExpenses($query, $limit);
    }
}
