<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Data\CategoryData;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class InMemoryCategoryRepository implements CategoryRepository
{
    /** @var array<string, Category> */
    public array $categories = [];

    /** @var list<string> ids of categories that expenses refer to */
    public array $used = [];

    public function add(string $name, bool $system = false, bool $archived = false): Category
    {
        $category = new Category;
        $category->forceFill([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'is_system' => $system,
            'translation_key' => $system ? $name : null,
            'archived_at' => $archived ? Carbon::now() : null,
        ]);

        return $this->categories[$category->id] = $category;
    }

    public function all(bool $includeArchived): array
    {
        return array_values(array_filter(
            $this->categories,
            static fn (Category $category): bool => $includeArchived || ! $category->isArchived(),
        ));
    }

    public function get(string $id): Category
    {
        return $this->categories[$id] ?? throw ResourceNotFoundException::for('category');
    }

    public function exists(string $id, bool $includeArchived): bool
    {
        $category = $this->categories[$id] ?? null;

        return $category !== null && ($includeArchived || ! $category->isArchived());
    }

    public function create(CategoryData $data): Category
    {
        $category = $this->add($data->name);
        $category->forceFill(['color' => $data->color, 'icon' => $data->icon]);

        return $category;
    }

    public function update(Category $category, CategoryData $data): Category
    {
        $category->forceFill(['name' => $data->name, 'color' => $data->color, 'icon' => $data->icon]);

        return $category;
    }

    public function delete(Category $category): void
    {
        unset($this->categories[$category->id]);
    }

    public function hasExpenses(Category $category): bool
    {
        return in_array($category->id, $this->used, true);
    }

    public function archive(Category $category): Category
    {
        $category->forceFill(['archived_at' => Carbon::now()]);

        return $category;
    }

    public function unarchive(Category $category): Category
    {
        $category->forceFill(['archived_at' => null]);

        return $category;
    }
}
