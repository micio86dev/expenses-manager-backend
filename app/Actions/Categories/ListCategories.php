<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;

final readonly class ListCategories
{
    public function __construct(private CategoryRepository $categories) {}

    /**
     * Sorted by the name the client sees, which for system categories depends on the locale.
     *
     * @return list<Category>
     */
    public function __invoke(bool $includeArchived): array
    {
        $categories = $this->categories->all($includeArchived);

        usort(
            $categories,
            static fn (Category $a, Category $b): int => strcmp(mb_strtolower($a->displayName()), mb_strtolower($b->displayName())),
        );

        return $categories;
    }
}
