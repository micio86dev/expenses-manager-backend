<?php

declare(strict_types=1);

/*
 * Pest's arch expectations see class dependencies, not static calls such as
 * `User::where(...)`. This test closes the gap by scanning the source of every
 * layer that must stay persistence-free for Eloquent/DB entry points.
 *
 * Allowed places (the only ones that may build queries):
 *   - app/Repositories/Eloquent  persistence implementations
 *   - app/Models                 model definitions
 *   - app/Support/Owner          the global scope and creation hook of OwnedByCurrentOwner
 *   - app/Support/Health         the readiness probe talks to the schema builder
 * Named exception: validation rules such as `unique:users,email` reference a table by
 * name inside FormRequests; that is a string rule, not model access, and is not matched here.
 */

const ENTRY_POINTS = [
    'query', 'where', 'whereIn', 'whereKey', 'create', 'forceCreate', 'find', 'findOrFail', 'findMany',
    'first', 'firstOrFail', 'firstOrCreate', 'firstOrNew', 'updateOrCreate', 'all', 'insert', 'upsert',
    'update', 'delete', 'destroy', 'select', 'table', 'raw', 'withoutGlobalScopes', 'on', 'has', 'with',
];

/**
 * @return list<string>
 */
function guardedSourceFiles(): array
{
    $root = dirname(__DIR__, 2).'/app';
    $allowed = ['Repositories/Eloquent', 'Models', 'Support/Owner', 'Support/Health'];
    $files = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        $relative = substr((string) $file, strlen($root) + 1);

        if (! str_ends_with($relative, '.php')) {
            continue;
        }

        foreach ($allowed as $directory) {
            if (str_starts_with($relative, $directory.'/')) {
                continue 2;
            }
        }

        $files[] = (string) $file;
    }

    return $files;
}

it('scans a meaningful set of files', function (): void {
    expect(guardedSourceFiles())->toContain(dirname(__DIR__, 2).'/app/Actions/Auth/LoginUser.php');
});

it('keeps query building out of controllers, actions, requests, resources and middleware', function (): void {
    $entryPoints = implode('|', ENTRY_POINTS);
    $pattern = "/\\b[A-Z][A-Za-z0-9_]*::(?:{$entryPoints})\\s*\\(|\\bDB::|->(?:{$entryPoints})\\s*\\(\\s*function/";
    $offences = [];

    foreach (guardedSourceFiles() as $file) {
        $source = (string) file_get_contents($file);

        if (preg_match_all($pattern, $source, $matches) > 0) {
            $offences[basename($file)] = $matches[0];
        }
    }

    expect($offences)->toBe([], 'Query building found outside repositories/models: '.json_encode($offences));
});

it('detects a violation (the scanner itself works)', function (): void {
    $pattern = '/\b[A-Z][A-Za-z0-9_]*::(?:query|where)\s*\(/';

    expect(preg_match($pattern, 'return User::query()->first();'))->toBe(1)
        ->and(preg_match($pattern, 'return Str::lower($x);'))->toBe(0);
});
