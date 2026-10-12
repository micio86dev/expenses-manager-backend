<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;

final class InvalidCredentialsException extends AuthenticationException implements LocalizedProblem
{
    public function problemDetail(): string
    {
        return __('auth.failed');
    }
}
