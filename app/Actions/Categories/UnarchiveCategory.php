<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Exceptions\SystemCategoryReadOnlyException;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;

final readonly class UnarchiveCategory
{
    public function __construct(private CategoryRepository $categories) {}

    public function __invoke(string $id): Category
    {
        $category = $this->categories->get($id);

        if ($category->is_system) {
            throw new SystemCategoryReadOnlyException;
        }

        return $category->isArchived() ? $this->categories->unarchive($category) : $category;
    }
}
