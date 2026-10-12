<?php

declare(strict_types=1);

namespace App\Data\Stats;

final readonly class Totals
{
    public function __construct(
        public int $totalCents,
        public int $count,
    ) {}
}
