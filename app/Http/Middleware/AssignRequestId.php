<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Propagates a valid inbound `X-Request-Id` (or generates a UUID), adds it to the
 * log context and echoes it on the response.
 */
final class AssignRequestId
{
    public const string HEADER = 'X-Request-Id';

    private const string VALID_ID = '/^[A-Za-z0-9._-]{8,64}$/';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $inbound = $request->headers->get(self::HEADER);
        $requestId = is_string($inbound) && preg_match(self::VALID_ID, $inbound) === 1
            ? $inbound
            : (string) Str::uuid();

        $request->headers->set(self::HEADER, $requestId);
        Log::shareContext(['request_id' => $requestId]);

        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        // Shared context otherwise outlives the request in long-lived workers.
        Log::withoutContext(['request_id'])->flushSharedContext();
    }
}
