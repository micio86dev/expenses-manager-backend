<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class CategoryInUseException extends HttpException implements LocalizedProblem
{
    public function __construct()
    {
        parent::__construct(409, 'The category is used by expenses.');
    }

    public function problemDetail(): string
    {
        return __('categories.errors.in_use');
    }
}
