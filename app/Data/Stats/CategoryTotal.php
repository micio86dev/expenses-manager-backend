<?php

declare(strict_types=1);

namespace App\Data\Stats;

/**
 * What one category cost in a range, with its name already resolved for the request locale.
 */
final readonly class CategoryTotal
{
    public function __construct(
        public string $categoryId,
        public string $name,
        public int $totalCents,
        public int $count,
    ) {}
}
