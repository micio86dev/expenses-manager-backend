<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\Model;

afterEach(function (): void {
    Model::preventLazyLoading(false);
});

it('boots with strict eloquent behaviour', function (): void {
    expect(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue()
        ->and(Model::preventsAccessingMissingAttributes())->toBeTrue();
});

it('relaxes the lazy loading guard in production only', function (string $environment, bool $prevented): void {
    $this->app->detectEnvironment(fn (): string => $environment);

    (new AppServiceProvider($this->app))->boot();

    expect(Model::preventsLazyLoading())->toBe($prevented);
})->with([
    'local' => ['local', true],
    'testing' => ['testing', true],
    'production' => ['production', false],
]);
