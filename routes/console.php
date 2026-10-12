<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Expired tokens are rejected by Sanctum already; this only keeps the table small.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
