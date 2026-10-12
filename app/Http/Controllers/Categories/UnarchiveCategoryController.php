<?php

declare(strict_types=1);

namespace App\Http\Controllers\Categories;

use App\Actions\Categories\UnarchiveCategory;
use App\Http\Resources\CategoryResource;

final readonly class UnarchiveCategoryController
{
    public function __invoke(string $category, UnarchiveCategory $action): CategoryResource
    {
        return new CategoryResource($action($category));
    }
}
