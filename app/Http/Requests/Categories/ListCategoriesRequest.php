<?php

declare(strict_types=1);

namespace App\Http\Requests\Categories;

use Illuminate\Foundation\Http\FormRequest;

final class ListCategoriesRequest extends FormRequest
{
    /**
     * The contract documents no 422 for this operation, so the flag is parsed leniently:
     * anything other than a truthy value ("true", "1", "on", "yes") means "hide archived".
     *
     * @return array<string, never>
     */
    public function rules(): array
    {
        return [];
    }

    public function includeArchived(): bool
    {
        return $this->boolean('include_archived');
    }
}
