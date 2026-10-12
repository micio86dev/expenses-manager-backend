<?php

declare(strict_types=1);

function langDirectory(): string
{
    return dirname(__DIR__, 2).'/lang';
}

/**
 * @param  array<string, mixed>  $lines
 * @return array<string, string>
 */
function flattenTranslations(array $lines, string $prefix = ''): array
{
    $flat = [];

    foreach ($lines as $key => $value) {
        $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

        if (is_array($value)) {
            $flat += flattenTranslations($value, $path);
        } else {
            $flat[$path] = (string) $value;
        }
    }

    return $flat;
}

/**
 * @return list<string>
 */
function translationFiles(string $locale): array
{
    $files = array_map(basename(...), glob(langDirectory()."/{$locale}/*.php") ?: []);
    sort($files);

    return $files;
}

/**
 * @return array<string, string>
 */
function loadTranslations(string $locale, string $file): array
{
    /** @var array<string, mixed> $lines */
    $lines = require langDirectory()."/{$locale}/{$file}";

    return flattenTranslations($lines);
}

/**
 * @return list<string>
 */
function placeholders(string $line): array
{
    preg_match_all('/:[A-Za-z_]+/', $line, $matches);
    $found = array_unique($matches[0]);
    sort($found);

    return array_values($found);
}

it('ships a translation folder for every supported locale', function (): void {
    foreach (config('locales.supported') as $locale) {
        expect(is_dir(langDirectory()."/{$locale}"))->toBeTrue("lang/{$locale} is missing");
    }

    expect(config('locales.supported'))->toContain(config('locales.default'));
});

it('provides the same translation files in every locale', function (): void {
    $reference = translationFiles('en');

    expect($reference)->not->toBeEmpty();

    foreach (config('locales.supported') as $locale) {
        expect(translationFiles($locale))->toBe($reference, "lang/{$locale} has a different set of files");
    }
});

it('defines identical translation keys in every locale', function (string $file): void {
    $reference = array_keys(loadTranslations('en', $file));
    sort($reference);

    foreach (config('locales.supported') as $locale) {
        $keys = array_keys(loadTranslations($locale, $file));
        sort($keys);

        expect($keys)->toBe($reference, "lang/{$locale}/{$file} keys differ from lang/en/{$file}");
    }
})->with(fn (): array => array_combine(translationFiles('en'), array_map(fn (string $f): array => [$f], translationFiles('en'))));

it('keeps the same placeholders in every translation', function (string $file): void {
    $reference = loadTranslations('en', $file);

    foreach (config('locales.supported') as $locale) {
        foreach (loadTranslations($locale, $file) as $key => $line) {
            expect(placeholders($line))->toBe(placeholders($reference[$key]), "{$locale}/{$file}: {$key}");
        }
    }
})->with(fn (): array => array_combine(translationFiles('en'), array_map(fn (string $f): array => [$f], translationFiles('en'))));

it('has no empty translation values', function (): void {
    foreach (config('locales.supported') as $locale) {
        foreach (translationFiles($locale) as $file) {
            foreach (loadTranslations($locale, $file) as $key => $line) {
                expect(trim($line))->not->toBe('', "{$locale}/{$file}: {$key} is empty");
            }
        }
    }
});
