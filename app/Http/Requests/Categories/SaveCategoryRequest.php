<?php

declare(strict_types=1);

namespace App\Http\Requests\Categories;

use App\Data\CategoryData;
use App\Enums\CategoryIcon;
use App\Support\Owner\CurrentOwner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create and update share the same input; on update the category may keep its own name.
 */
final class SaveCategoryRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(CurrentOwner $owner): array
    {
        $category = $this->route('category');

        return [
            'name' => [
                'required', 'string', 'min:1', 'max:60',
                // A string rule on the table name, scoped to the owner: validation, not model access.
                Rule::unique('categories', 'name')
                    ->where('owner_id', $owner->id())
                    ->ignore(is_string($category) ? $category : null),
            ],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['required', 'string', Rule::enum(CategoryIcon::class)],
        ];
    }

    public function toData(): CategoryData
    {
        $input = $this->safe();

        return new CategoryData(
            name: $input->string('name')->toString(),
            color: strtoupper($input->string('color')->toString()),
            icon: CategoryIcon::from($input->string('icon')->toString()),
        );
    }
}
