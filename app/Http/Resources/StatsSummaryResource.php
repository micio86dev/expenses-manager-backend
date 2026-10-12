<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\Stats\StatsSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property StatsSummary $resource
 */
final class StatsSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $summary = $this->resource;

        return [
            'currency' => $summary->currency,
            'total_cents' => $summary->totalCents,
            'count' => $summary->count,
            'daily_average_cents' => $summary->dailyAverageCents,
            'previous_period' => [
                'total_cents' => $summary->previousTotalCents,
                'delta_percent' => $summary->deltaPercent,
            ],
            'top_category' => $summary->topCategory === null ? null : CategoryStatsCollection::item($summary->topCategory),
            'biggest_expense' => $summary->biggestExpense === null ? null : (new ExpenseResource($summary->biggestExpense))->resolve($request),
        ];
    }
}
