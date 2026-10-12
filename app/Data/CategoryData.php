<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CategoryIcon;

/**
 * The editable fields of a custom category.
 */
final readonly class CategoryData
{
    public function __construct(
        public string $name,
        public string $color,
        public CategoryIcon $icon,
    ) {}
}
