<?php

declare(strict_types=1);

namespace App\Http\Controllers\Categories;

use App\Actions\Categories\CreateCategory;
use App\Http\Requests\Categories\SaveCategoryRequest;
use App\Http\Resources\CategoryResource;

final readonly class CreateCategoryController
{
    public function __invoke(SaveCategoryRequest $request, CreateCategory $create): CategoryResource
    {
        // A freshly created model is answered with 201 by the resource itself.
        return new CategoryResource($create($request->toData()));
    }
}
