<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stats;

use App\Actions\Stats\GetCategoryStats;
use App\Http\Requests\Stats\StatsRangeRequest;
use App\Http\Resources\CategoryStatsCollection;

final readonly class StatsByCategoryController
{
    public function __invoke(StatsRangeRequest $request, GetCategoryStats $categories): CategoryStatsCollection
    {
        return new CategoryStatsCollection($categories($request->toStatsQuery()));
    }
}
