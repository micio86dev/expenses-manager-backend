<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class SystemCategoryReadOnlyException extends HttpException implements LocalizedProblem
{
    public function __construct()
    {
        parent::__construct(403, 'System categories are read-only.');
    }

    public function problemDetail(): string
    {
        return __('categories.errors.system_read_only');
    }
}
