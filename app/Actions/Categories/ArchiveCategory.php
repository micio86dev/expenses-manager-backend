<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Exceptions\SystemCategoryReadOnlyException;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;

/**
 * Idempotent: archiving an archived category changes nothing, so the first archive time is kept.
 * System categories are shared by every owner, so one owner cannot archive them for the others.
 */
final readonly class ArchiveCategory
{
    public function __construct(private CategoryRepository $categories) {}

    public function __invoke(string $id): Category
    {
        $category = $this->categories->get($id);

        if ($category->is_system) {
            throw new SystemCategoryReadOnlyException;
        }

        return $category->isArchived() ? $category : $this->categories->archive($category);
    }
}
