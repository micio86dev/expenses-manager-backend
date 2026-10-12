<?php

declare(strict_types=1);

use App\Support\Locale\LocaleRegistry;
use Illuminate\Config\Repository;
use Illuminate\Http\Request;

function localeRegistry(): LocaleRegistry
{
    return new LocaleRegistry(new Repository(['locales' => ['supported' => ['it', 'en'], 'default' => 'it']]));
}

function requestAccepting(?string $header): Request
{
    $request = Request::create('/');
    $request->headers->remove('Accept-Language');

    if ($header !== null) {
        $request->headers->set('Accept-Language', $header);
    }

    return $request;
}

it('exposes the configured allowlist and default', function (): void {
    expect(localeRegistry()->supported())->toBe(['it', 'en'])
        ->and(localeRegistry()->default())->toBe('it')
        ->and(localeRegistry()->isSupported('en'))->toBeTrue()
        ->and(localeRegistry()->isSupported('de'))->toBeFalse()
        ->and(localeRegistry()->isSupported(null))->toBeFalse();
});

it('matches Accept-Language against the allowlist', function (?string $header, ?string $expected): void {
    expect(localeRegistry()->fromAcceptLanguage(requestAccepting($header)))->toBe($expected);
})->with([
    'missing header' => [null, null],
    'exact match' => ['en', 'en'],
    'region falls back to primary' => ['it-CH', 'it'],
    'underscore separator' => ['en_GB', 'en'],
    'case insensitive' => ['EN-us', 'en'],
    'quality order' => ['en;q=0.3, it;q=0.9', 'it'],
    'skips unsupported' => ['de, fr;q=0.9, en;q=0.1', 'en'],
    'nothing supported' => ['de, fr', null],
    'wildcard only' => ['*', null],
]);
