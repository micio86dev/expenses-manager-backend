<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\Stats\CategoryStat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Spending per category, largest first.
 *
 * @property list<CategoryStat> $resource
 */
final class CategoryStatsCollection extends JsonResource
{
    // The body below already carries `data`; Laravel must not wrap it a second time.
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['data' => array_map(self::item(...), $this->resource)];
    }

    /**
     * @return array<string, mixed>
     */
    public static function item(CategoryStat $stat): array
    {
        return [
            'category_id' => $stat->categoryId,
            'name' => $stat->name,
            'total_cents' => $stat->totalCents,
            'count' => $stat->count,
            'share_percent' => $stat->sharePercent,
        ];
    }
}
