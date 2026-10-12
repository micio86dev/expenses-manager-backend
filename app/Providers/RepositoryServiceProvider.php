<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\AccessTokenRepository;
use App\Repositories\Contracts\CategoryRepository;
use App\Repositories\Contracts\ExpenseRepository;
use App\Repositories\Contracts\StatsRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Eloquent\EloquentCategoryRepository;
use App\Repositories\Eloquent\EloquentExpenseRepository;
use App\Repositories\Eloquent\EloquentStatsRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\Eloquent\SanctumAccessTokenRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepository::class => EloquentUserRepository::class,
        AccessTokenRepository::class => SanctumAccessTokenRepository::class,
        CategoryRepository::class => EloquentCategoryRepository::class,
        ExpenseRepository::class => EloquentExpenseRepository::class,
        StatsRepository::class => EloquentStatsRepository::class,
    ];
}
