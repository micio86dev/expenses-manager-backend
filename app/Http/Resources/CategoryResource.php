<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Category $resource
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->displayName(),
            'color' => $this->resource->color,
            'icon' => $this->resource->icon->value,
            'is_system' => $this->resource->is_system,
            'archived' => $this->resource->isArchived(),
            'created_at' => $this->resource->created_at->toIso8601String(),
        ];
    }
}
