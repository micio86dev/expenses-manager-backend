<?php

declare(strict_types=1);

namespace App\Http\Controllers\Categories;

use App\Actions\Categories\GetCategory;
use App\Http\Resources\CategoryResource;

final readonly class ShowCategoryController
{
    public function __invoke(string $category, GetCategory $action): CategoryResource
    {
        return new CategoryResource($action($category));
    }
}
