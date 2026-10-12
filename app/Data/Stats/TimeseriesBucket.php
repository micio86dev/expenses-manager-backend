<?php

declare(strict_types=1);

namespace App\Data\Stats;

use Carbon\CarbonImmutable;

final readonly class TimeseriesBucket
{
    public function __construct(
        public CarbonImmutable $periodStart,
        public int $totalCents,
        public int $count,
    ) {}
}
