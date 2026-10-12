<?php

declare(strict_types=1);

namespace App\Http\Controllers\Categories;

use App\Actions\Categories\UpdateCategory;
use App\Http\Requests\Categories\SaveCategoryRequest;
use App\Http\Resources\CategoryResource;

final readonly class UpdateCategoryController
{
    public function __invoke(SaveCategoryRequest $request, string $category, UpdateCategory $update): CategoryResource
    {
        return new CategoryResource($update($category, $request->toData()));
    }
}
