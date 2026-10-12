<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    Route::get('/_client-ip', fn (Request $request): string => (string) $request->ip());
});

function viaProxy(): TestResponse
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
        ->withHeader('X-Forwarded-For', '203.0.113.9')
        ->get('/_client-ip');
}

it('ignores forwarded headers when no proxy is trusted', function (): void {
    config(['trustedproxy.proxies' => null]);

    viaProxy()->assertOk()->assertSee('10.0.0.5');
});

it('uses the forwarded client ip behind a trusted proxy range', function (): void {
    config(['trustedproxy.proxies' => '10.0.0.0/8, 192.168.0.0/16']);

    viaProxy()->assertOk()->assertSee('203.0.113.9');
});

it('ignores forwarded headers from an address outside the trusted range', function (): void {
    config(['trustedproxy.proxies' => '192.168.0.0/16']);

    viaProxy()->assertOk()->assertSee('10.0.0.5');
});

it('trusts no proxy unless TRUSTED_PROXIES is set', function (?string $value, mixed $expected): void {
    $repository = Env::getRepository();
    $previous = $repository->get('TRUSTED_PROXIES');
    $value === null ? $repository->clear('TRUSTED_PROXIES') : $repository->set('TRUSTED_PROXIES', $value);

    try {
        $config = require config_path('trustedproxy.php');
    } finally {
        $previous === null ? $repository->clear('TRUSTED_PROXIES') : $repository->set('TRUSTED_PROXIES', $previous);
    }

    expect($config['proxies'])->toBe($expected);
})->with([
    'unset' => [null, null],
    'empty' => ['', null],
    'blank' => ['  ', null],
    'ranges' => ['10.0.0.0/8,172.16.0.0/12', '10.0.0.0/8,172.16.0.0/12'],
]);
