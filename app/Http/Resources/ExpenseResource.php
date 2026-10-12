<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Expense;
use App\Support\DateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Expense $resource
 */
final class ExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'description' => $this->resource->description,
            'amount_cents' => $this->resource->amount_cents,
            'currency' => $this->resource->currency,
            'category_id' => $this->resource->category_id,
            'spent_on' => $this->resource->spent_on->format(DateRange::FORMAT),
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        if ($response->getStatusCode() === 201) {
            $response->header('Location', url("/api/v1/expenses/{$this->resource->id}"));
        }
    }
}
