<?php

declare(strict_types=1);

use App\Http\Controllers\Health\HealthController;
use App\Http\Controllers\Health\ReadinessController;
use Illuminate\Support\Facades\Route;

// Probes live outside /api/v1 and use no group middleware: no session, no auth.
Route::get('/health', HealthController::class);
Route::get('/ready', ReadinessController::class);
