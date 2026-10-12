<?php

declare(strict_types=1);
use Illuminate\Contracts\Validation\ValidationRule;

arch('every source file declares strict types')
    ->expect(['App', 'Database', 'Tests'])
    ->toUseStrictTypes();

// There are no abstract classes in app/; if one is ever needed, list it in ->ignoring() with a reason.
arch('application classes are final by default')
    ->expect('App')
    ->classes()
    ->toBeFinal();

arch('data transfer objects are final readonly classes')
    ->expect('App\Data')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('value objects are final, readonly and know nothing about the framework layers')
    ->expect(['App\Support\Money', 'App\Support\DateRange'])
    ->toBeFinal()
    ->toBeReadonly()
    ->not->toUse(['Illuminate\Http', 'Illuminate\Database', 'App\Models']);

arch('enums are enums')
    ->expect('App\Enums')
    ->toBeEnums();

arch('validation rules depend on repository contracts, never on Eloquent implementations')
    ->expect('App\Rules')
    ->toImplement(ValidationRule::class)
    ->not->toUse(['App\Repositories\Eloquent', 'Illuminate\Database']);

arch('repository contracts are interfaces and implementations implement one')
    ->expect('App\Repositories\Contracts')
    ->toBeInterfaces();

it('binds every eloquent repository to a contract', function (): void {
    $classes = collect(glob(dirname(__DIR__, 2).'/app/Repositories/Eloquent/*.php'))
        ->map(fn (string $file): string => 'App\\Repositories\\Eloquent\\'.basename($file, '.php'));

    expect($classes)->not->toBeEmpty();

    foreach ($classes as $class) {
        $contracts = collect(class_implements($class))
            ->filter(fn (string $interface): bool => str_starts_with($interface, 'App\\Repositories\\Contracts\\'));

        expect($contracts)->not->toBeEmpty("{$class} does not implement a repository contract");
    }
});

arch('controllers only depend on form requests, actions, resources and http responses')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toOnlyUse([
        'App\Actions',
        'App\Http\Requests',
        'App\Http\Resources',
        'Illuminate\Http\JsonResponse',
        'Illuminate\Http\Response',
    ]);

arch('controllers are single-action and named accordingly')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller')
    ->toHaveMethod('__invoke');

arch('actions know nothing about HTTP, facades or the database')
    ->expect('App\Actions')
    ->not->toUse([
        'Illuminate\Http',
        'Illuminate\Support\Facades',
        'Illuminate\Database',
        'Illuminate\Routing',
    ]);

arch('actions depend on repository contracts, never on Eloquent implementations')
    ->expect('App\Actions')
    ->not->toUse('App\Repositories\Eloquent');

// Named exception: AppServiceProvider configures global model behaviour (strict mode,
// lazy-loading guard) and therefore has to reference the base Model class.
arch('eloquent classes are only used by models, repositories, ownership support and factories')
    ->expect('Illuminate\Database\Eloquent')
    ->toOnlyBeUsedIn([
        'App\Providers\AppServiceProvider',
        'App\Models',
        'App\Repositories\Eloquent',
        'App\Support\Owner',
        'Database\Factories',
        'Tests\Fixtures',
    ]);

arch('models are only referenced by layers that pass them around, never by controllers')
    ->expect('App\Models')
    ->toOnlyBeUsedIn([
        'App\Actions',
        'App\Data',
        'App\Http\Requests',
        'App\Http\Resources',
        'App\Http\Middleware',
        'App\Observers',
        'App\Providers',
        'App\Repositories',
        'Database',
        'Tests',
    ]);

arch('the database facade is not used in the application layers')
    ->expect('Illuminate\Support\Facades\DB')
    ->not->toBeUsedIn(['App\Actions', 'App\Http', 'App\Data']);

arch('form requests and resources do not touch the persistence layer')
    ->expect(['App\Http\Requests', 'App\Http\Resources'])
    ->not->toUse(['Illuminate\Database', 'App\Repositories']);

arch('exceptions that carry a message for clients are localized problems or http exceptions')
    ->expect('App\Exceptions')
    ->toExtend(Throwable::class)
    ->ignoring('App\Exceptions\LocalizedProblem');

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die', 'exit'])
    ->not->toBeUsed();

arch()->preset()->php();

arch()->preset()->security();
