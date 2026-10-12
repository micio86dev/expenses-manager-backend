<?php

declare(strict_types=1);

use App\Support\Stats\StatsVersion;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;

function statsVersions(): StatsVersion
{
    return new StatsVersion(new Repository(new ArrayStore));
}

it('hands out a stable version until the owner is bumped', function (): void {
    $versions = statsVersions();

    expect($versions->current('alice'))->toBe($versions->current('alice'));
});

it('changes the version of one owner and leaves the others alone', function (): void {
    $versions = statsVersions();
    $before = ['alice' => $versions->current('alice'), 'bob' => $versions->current('bob')];

    $versions->bump('alice');

    expect($versions->current('alice'))->not->toBe($before['alice'])
        ->and($versions->current('bob'))->toBe($before['bob']);
});

it('never reuses a version, even after the stored one is evicted', function (): void {
    $cache = new Repository(new ArrayStore);
    $versions = new StatsVersion($cache);
    $first = $versions->current('alice');

    $cache->flush();

    expect($versions->current('alice'))->not->toBe($first);
});

it('namespaces the version key by owner', function (): void {
    expect(StatsVersion::key('alice'))->toBe('stats:version:alice');
});
