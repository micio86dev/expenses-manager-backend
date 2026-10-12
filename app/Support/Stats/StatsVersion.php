<?php

declare(strict_types=1);

namespace App\Support\Stats;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Str;

/**
 * One opaque version token per owner. Cached statistics embed the token in their key, so a
 * write only has to replace the token: the old entries become unreachable and expire on their
 * own. Works on every cache driver (no tags, no key scanning).
 */
final readonly class StatsVersion
{
    public function __construct(private Repository $cache) {}

    public static function key(string $ownerId): string
    {
        return "stats:version:{$ownerId}";
    }

    public function current(string $ownerId): string
    {
        $key = self::key($ownerId);
        $version = $this->cache->get($key);

        if (is_string($version)) {
            return $version;
        }

        $version = (string) Str::ulid();

        // add() keeps the token another request created first, so concurrent readers agree.
        $this->cache->add($key, $version);

        $stored = $this->cache->get($key);

        return is_string($stored) ? $stored : $version;
    }

    public function bump(string $ownerId): void
    {
        // A fresh random token, not an increment: increments are not atomic or even available on every driver.
        $this->cache->forever(self::key($ownerId), (string) Str::ulid());
    }
}
