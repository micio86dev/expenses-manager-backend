<?php

declare(strict_types=1);

use App\Enums\CategoryIcon;
use App\Enums\SystemCategory;

it('has a translation in every locale for every system category', function (SystemCategory $category): void {
    foreach (config('locales.supported') as $locale) {
        expect(trans($category->translationKey(), [], $locale))->not->toBe($category->translationKey());
    }
})->with(SystemCategory::cases());

it('defines a valid color and icon for every system category', function (SystemCategory $category): void {
    expect($category->color())->toMatch('/^#[0-9A-Fa-f]{6}$/')
        ->and($category->icon())->toBeInstanceOf(CategoryIcon::class);
})->with(SystemCategory::cases());

it('uses unique keys', function (): void {
    $keys = array_map(fn (SystemCategory $category): string => $category->value, SystemCategory::cases());

    expect($keys)->toBe(array_values(array_unique($keys)));
});
