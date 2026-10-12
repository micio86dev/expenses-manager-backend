<?php

declare(strict_types=1);

namespace App\Data\Stats;

use Carbon\CarbonImmutable;

/**
 * Everything spent on one calendar day: the portable unit week and month buckets are built from.
 */
final readonly class DayTotal
{
    public function __construct(
        public CarbonImmutable $day,
        public int $totalCents,
        public int $count,
    ) {}
}
