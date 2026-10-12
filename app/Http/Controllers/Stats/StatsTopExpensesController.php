<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stats;

use App\Actions\Stats\GetTopExpenses;
use App\Http\Requests\Stats\TopExpensesRequest;
use App\Http\Resources\ExpenseResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class StatsTopExpensesController
{
    public function __invoke(TopExpensesRequest $request, GetTopExpenses $top): AnonymousResourceCollection
    {
        return ExpenseResource::collection($top($request->toStatsQuery(), $request->limit()));
    }
}
