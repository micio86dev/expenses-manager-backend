<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ShowProfileController;
use App\Http\Controllers\Auth\UpdateProfileController;
use Illuminate\Support\Facades\Route;

// Served under /api/v1 (see bootstrap/app.php).
Route::prefix('auth')->group(function (): void {
    Route::post('register', RegisterController::class)->middleware('throttle:register');
    Route::post('login', LoginController::class)->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::post('logout', LogoutController::class);
        Route::get('me', ShowProfileController::class);
        Route::patch('me', UpdateProfileController::class);
    });
});
