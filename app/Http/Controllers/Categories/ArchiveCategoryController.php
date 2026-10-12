<?php

declare(strict_types=1);

namespace App\Http\Controllers\Categories;

use App\Actions\Categories\ArchiveCategory;
use App\Http\Resources\CategoryResource;

final readonly class ArchiveCategoryController
{
    public function __invoke(string $category, ArchiveCategory $action): CategoryResource
    {
        return new CategoryResource($action($category));
    }
}
