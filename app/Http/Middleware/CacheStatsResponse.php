<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Owner\CurrentOwner;
use App\Support\Stats\StatsVersion;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Caches the JSON body of successful statistics responses per owner.
 *
 * The key holds the owner's version token (see {@see StatsVersion}), the locale (category
 * names are translated) and the normalised query. The token is read BEFORE the response is
 * computed: if a write lands meanwhile, the body is stored under the old token and is never read.
 */
final readonly class CacheStatsResponse
{
    public const int TTL_SECONDS = 3600;

    /** Query parameters that shape a statistic; anything else must not create cache entries. */
    private const array QUERY_PARAMETERS = ['currency', 'from', 'granularity', 'to'];

    public function __construct(
        private CurrentOwner $owner,
        private StatsVersion $versions,
        private Repository $cache,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ownerId = $this->owner->id();

        if ($ownerId === null) {
            return $next($request);
        }

        $key = $this->key($request, $ownerId);
        $cached = $this->cache->get($key);

        if (is_string($cached)) {
            return JsonResponse::fromJsonString($cached);
        }

        $response = $next($request);

        if ($response->getStatusCode() === Response::HTTP_OK && is_string($body = $response->getContent())) {
            $this->cache->put($key, $body, self::TTL_SECONDS);
        }

        return $response;
    }

    private function key(Request $request, string $ownerId): string
    {
        $query = $request->query();
        $relevant = [];

        foreach (self::QUERY_PARAMETERS as $parameter) {
            if (isset($query[$parameter]) && is_string($query[$parameter])) {
                $relevant[$parameter] = $query[$parameter];
            }
        }

        $fingerprint = sha1(implode('|', [app()->getLocale(), $request->path(), http_build_query($relevant)]));

        return "stats:{$ownerId}:{$this->versions->current($ownerId)}:{$fingerprint}";
    }
}
