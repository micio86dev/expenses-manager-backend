<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stats;

use App\Actions\Stats\GetTimeseries;
use App\Http\Requests\Stats\TimeseriesRequest;
use App\Http\Resources\TimeseriesCollection;

final readonly class StatsTimeseriesController
{
    public function __invoke(TimeseriesRequest $request, GetTimeseries $timeseries): TimeseriesCollection
    {
        return new TimeseriesCollection($timeseries($request->toStatsQuery(), $request->granularity()));
    }
}
