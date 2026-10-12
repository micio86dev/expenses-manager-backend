<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Implemented by exceptions that carry a safe, already localized message for the
 * `detail` member of a problem response. Any other exception gets a generic
 * localized detail so internal messages never reach the client.
 */
interface LocalizedProblem
{
    public function problemDetail(): string;
}
