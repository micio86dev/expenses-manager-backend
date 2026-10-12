<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Data\LoginData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class LoginRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge(['email' => Str::lower(trim($email))]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function toData(): LoginData
    {
        $input = $this->safe();

        return new LoginData(
            email: $input->string('email')->toString(),
            password: $input->string('password')->toString(),
        );
    }
}
