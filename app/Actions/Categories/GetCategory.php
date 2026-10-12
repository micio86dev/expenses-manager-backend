<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;

final readonly class GetCategory
{
    public function __construct(private CategoryRepository $categories) {}

    public function __invoke(string $id): Category
    {
        return $this->categories->get($id);
    }
}
