<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A resource does not exist or is not visible to the current owner (the two are
 * indistinguishable on purpose, so ids of other owners never leak).
 */
final class ResourceNotFoundException extends NotFoundHttpException
{
    public static function for(string $resource): self
    {
        return new self("The {$resource} was not found.");
    }
}
