<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\ResolvesAuthenticatedUser;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request of an authenticated route that takes no input.
 */
final class AuthenticatedRequest extends FormRequest
{
    use ResolvesAuthenticatedUser;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
