<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Exceptions\CategoryInUseException;
use App\Exceptions\SystemCategoryReadOnlyException;
use App\Repositories\Contracts\CategoryRepository;

final readonly class DeleteCategory
{
    public function __construct(private CategoryRepository $categories) {}

    public function __invoke(string $id): void
    {
        $category = $this->categories->get($id);

        if ($category->is_system) {
            throw new SystemCategoryReadOnlyException;
        }

        if ($this->categories->hasExpenses($category)) {
            throw new CategoryInUseException;
        }

        $this->categories->delete($category);
    }
}
