<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strong ETag for successful statistics responses: the quoted SHA-256 of the exact JSON body.
 * A matching `If-None-Match` (a list, weak validators and `*` included, compared weakly as
 * RFC 9110 requires for this header) turns the response into an empty 304.
 */
final readonly class AddStatsEtag
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return $response;
        }

        // Category names are translated, so shared caches must key the body on the language too.
        $response->setVary('Accept-Language', false);
        $response->setEtag(hash('sha256', (string) $response->getContent()));
        $response->isNotModified($request);

        return $response;
    }
}
