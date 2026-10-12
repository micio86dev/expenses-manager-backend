<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\Stats\TimeseriesBucket;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property list<TimeseriesBucket> $resource
 */
final class TimeseriesCollection extends JsonResource
{
    // The body below already carries `data`; Laravel must not wrap it a second time.
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => array_map(
                static fn (TimeseriesBucket $bucket): array => [
                    'period_start' => $bucket->periodStart->format(DateRange::FORMAT),
                    'total_cents' => $bucket->totalCents,
                    'count' => $bucket->count,
                ],
                $this->resource,
            ),
        ];
    }
}
