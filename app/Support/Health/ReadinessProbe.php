<?php

declare(strict_types=1);

namespace App\Support\Health;

use Throwable;

interface ReadinessProbe
{
    /**
     * @throws Throwable when a dependency cannot serve traffic
     */
    public function check(): void;
}
