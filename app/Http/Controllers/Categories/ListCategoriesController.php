<?php

declare(strict_types=1);

namespace App\Http\Controllers\Categories;

use App\Actions\Categories\ListCategories;
use App\Http\Requests\Categories\ListCategoriesRequest;
use App\Http\Resources\CategoryCollection;

final readonly class ListCategoriesController
{
    public function __invoke(ListCategoriesRequest $request, ListCategories $list): CategoryCollection
    {
        return new CategoryCollection($list($request->includeArchived()));
    }
}
