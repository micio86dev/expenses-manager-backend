<?php

declare(strict_types=1);

namespace Database\Seeders\Reference;

use App\Enums\SystemCategory;
use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Upserts the shared system categories by translation key. Safe to run repeatedly in
 * production: existing rows are updated in place and never duplicated.
 */
final class SystemCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (SystemCategory::cases() as $system) {
            $category = Category::query()
                ->withoutGlobalScopes()
                ->where('translation_key', $system->value)
                ->first() ?? new Category;

            // `owner_id` is set explicitly (NULL) so the ownership hook does not look for a current owner.
            // The stored name is the English fallback; the API translates it per request locale.
            $category->forceFill([
                'owner_id' => null,
                'translation_key' => $system->value,
                'name' => trans($system->translationKey(), [], 'en'),
                'color' => $system->color(),
                'icon' => $system->icon(),
                'is_system' => true,
            ])->save();
        }
    }
}
