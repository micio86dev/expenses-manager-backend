<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Owner\AuthenticatedUserOwner;
use App\Support\Owner\CurrentOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * The owner is the signed-in user today; switching to tenants is one binding change.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        CurrentOwner::class => AuthenticatedUserOwner::class,
    ];

    public function boot(): void
    {
        Model::shouldBeStrict();
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
