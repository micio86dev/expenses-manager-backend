<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Data\CategoryData;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;
use Illuminate\Database\Eloquent\Builder;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function all(bool $includeArchived): array
    {
        return array_values(Category::query()
            ->when(! $includeArchived, static fn (Builder $query) => $query->whereNull('archived_at'))
            ->orderBy('name')
            ->get()
            ->all());
    }

    public function get(string $id): Category
    {
        return Category::query()->find($id) ?? throw ResourceNotFoundException::for('category');
    }

    public function exists(string $id, bool $includeArchived): bool
    {
        return Category::query()
            ->whereKey($id)
            ->when(! $includeArchived, static fn (Builder $query) => $query->whereNull('archived_at'))
            ->exists();
    }

    public function create(CategoryData $data): Category
    {
        return Category::query()->create([
            'name' => $data->name,
            'color' => $data->color,
            'icon' => $data->icon,
        ]);
    }

    public function update(Category $category, CategoryData $data): Category
    {
        $category->fill([
            'name' => $data->name,
            'color' => $data->color,
            'icon' => $data->icon,
        ])->save();

        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function hasExpenses(Category $category): bool
    {
        return $category->expenses()->exists();
    }

    public function archive(Category $category): Category
    {
        $category->forceFill(['archived_at' => now()])->save();

        return $category;
    }

    public function unarchive(Category $category): Category
    {
        $category->forceFill(['archived_at' => null])->save();

        return $category;
    }
}
