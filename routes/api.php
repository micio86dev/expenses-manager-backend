<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ShowProfileController;
use App\Http\Controllers\Auth\UpdateProfileController;
use App\Http\Controllers\Categories\ArchiveCategoryController;
use App\Http\Controllers\Categories\CreateCategoryController;
use App\Http\Controllers\Categories\DeleteCategoryController;
use App\Http\Controllers\Categories\ListCategoriesController;
use App\Http\Controllers\Categories\ShowCategoryController;
use App\Http\Controllers\Categories\UnarchiveCategoryController;
use App\Http\Controllers\Categories\UpdateCategoryController;
use App\Http\Controllers\Expenses\CreateExpenseController;
use App\Http\Controllers\Expenses\DeleteExpenseController;
use App\Http\Controllers\Expenses\ListExpensesController;
use App\Http\Controllers\Expenses\ShowExpenseController;
use App\Http\Controllers\Expenses\UpdateExpenseController;
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

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('categories')->group(function (): void {
        Route::get('/', ListCategoriesController::class);
        Route::post('/', CreateCategoryController::class);

        Route::prefix('{category}')->whereUuid('category')->group(function (): void {
            Route::get('/', ShowCategoryController::class);
            Route::put('/', UpdateCategoryController::class);
            Route::delete('/', DeleteCategoryController::class);
            Route::post('archive', ArchiveCategoryController::class);
            Route::post('unarchive', UnarchiveCategoryController::class);
        });
    });

    Route::prefix('expenses')->group(function (): void {
        Route::get('/', ListExpensesController::class);
        Route::post('/', CreateExpenseController::class);

        Route::prefix('{expense}')->whereUuid('expense')->group(function (): void {
            Route::get('/', ShowExpenseController::class);
            Route::put('/', UpdateExpenseController::class);
            Route::delete('/', DeleteExpenseController::class);
        });
    });
});
