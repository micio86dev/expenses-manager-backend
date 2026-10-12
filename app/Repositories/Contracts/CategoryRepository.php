<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\CategoryData;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Category;

/**
 * Categories visible to the current owner: its own and the shared system ones.
 */
interface CategoryRepository
{
    /**
     * @return list<Category>
     */
    public function all(bool $includeArchived): array;

    /**
     * @throws ResourceNotFoundException
     */
    public function get(string $id): Category;

    public function exists(string $id, bool $includeArchived): bool;

    public function create(CategoryData $data): Category;

    public function update(Category $category, CategoryData $data): Category;

    public function delete(Category $category): void;

    public function hasExpenses(Category $category): bool;

    public function archive(Category $category): Category;

    public function unarchive(Category $category): Category;
}
