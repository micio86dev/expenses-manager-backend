<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\ExpensePage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A page of expenses with exactly the four pagination figures of the contract.
 *
 * @property ExpensePage $resource
 */
final class ExpenseCollection extends JsonResource
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
                static fn ($expense): array => (new ExpenseResource($expense))->resolve($request),
                $this->resource->items,
            ),
            'meta' => [
                'current_page' => $this->resource->currentPage,
                'per_page' => $this->resource->perPage,
                'total' => $this->resource->total,
                'last_page' => $this->resource->lastPage,
            ],
        ];
    }
}
