<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Data\CategoryData;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;

final readonly class CreateCategory
{
    public function __construct(private CategoryRepository $categories) {}

    public function __invoke(CategoryData $data): Category
    {
        return $this->categories->create($data);
    }
}
