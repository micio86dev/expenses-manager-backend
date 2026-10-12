<?php

declare(strict_types=1);

namespace App\Data\Stats;

final readonly class CategoryStat
{
    public function __construct(
        public string $categoryId,
        public string $name,
        public int $totalCents,
        public int $count,
        public float $sharePercent,
    ) {}
}
