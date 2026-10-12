<?php

declare(strict_types=1);

namespace App\Support\Problem;

use App\Exceptions\LocalizedProblem;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Renders any throwable as an RFC 9457 `application/problem+json` response with
 * a localized title and detail. Internal exception messages are never exposed.
 */
final readonly class ProblemDetailsRenderer
{
    public const string CONTENT_TYPE = 'application/problem+json';

    public function __construct(private Translator $translator) {}

    public function render(Throwable $exception): JsonResponse
    {
        $status = $this->statusFor($exception);
        $headers = ['Content-Type' => self::CONTENT_TYPE];

        if ($exception instanceof HttpExceptionInterface) {
            $headers = [...$exception->getHeaders(), ...$headers];
        }

        if ($status === 401) {
            $headers['WWW-Authenticate'] ??= 'Bearer';
        }

        $problem = [
            'type' => 'about:blank',
            'title' => $this->message($status, 'title'),
            'status' => $status,
            'detail' => $exception instanceof LocalizedProblem
                ? $exception->problemDetail()
                : $this->message($status, 'detail'),
        ];

        if ($exception instanceof ValidationException) {
            $problem['errors'] = $exception->errors();
        }

        return new JsonResponse($problem, $status, $headers, 0);
    }

    private function statusFor(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof ValidationException => $exception->status,
            $exception instanceof AuthenticationException => 401,
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            default => 500,
        };
    }

    /**
     * @param  'title'|'detail'  $part
     */
    private function message(int $status, string $part): string
    {
        $key = "errors.{$status}.{$part}";

        $message = $this->translator->has($key)
            ? $this->translator->get($key)
            : $this->translator->get("errors.default.{$part}");

        return is_string($message) ? $message : '';
    }
}
