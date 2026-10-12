<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

final class NotReadyException extends HttpException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(503, 'Dependency unavailable.', $previous);
    }
}
