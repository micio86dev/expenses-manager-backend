<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Support\Str;

/**
 * The one way an email is normalised for authentication: trimmed and lowercased.
 * Login validation and the login throttle key both use it, so spelling variants share a bucket.
 */
final class EmailKey
{
    /**
     * Null when the input is not a string (for example `email[]=x`), which validation rejects later.
     */
    public static function normalise(mixed $email): ?string
    {
        return is_string($email) ? Str::lower(trim($email)) : null;
    }
}
