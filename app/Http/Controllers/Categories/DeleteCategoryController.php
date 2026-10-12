<?php

declare(strict_types=1);

namespace App\Http\Controllers\Categories;

use App\Actions\Categories\DeleteCategory;
use Illuminate\Http\Response;

final readonly class DeleteCategoryController
{
    public function __invoke(string $category, DeleteCategory $delete): Response
    {
        $delete($category);

        return new Response(status: 204);
    }
}
