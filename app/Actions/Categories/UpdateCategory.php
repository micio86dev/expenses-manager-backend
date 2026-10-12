<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Data\CategoryData;
use App\Exceptions\SystemCategoryReadOnlyException;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;

final readonly class UpdateCategory
{
    public function __construct(private CategoryRepository $categories) {}

    public function __invoke(string $id, CategoryData $data): Category
    {
        $category = $this->categories->get($id);

        if ($category->is_system) {
            throw new SystemCategoryReadOnlyException;
        }

        return $this->categories->update($category, $data);
    }
}
