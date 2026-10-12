<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stats;

use App\Actions\Stats\GetStatsSummary;
use App\Http\Requests\Stats\StatsRangeRequest;
use App\Http\Resources\StatsSummaryResource;

final readonly class StatsSummaryController
{
    public function __invoke(StatsRangeRequest $request, GetStatsSummary $summary): StatsSummaryResource
    {
        return new StatsSummaryResource($summary($request->toStatsQuery()));
    }
}
