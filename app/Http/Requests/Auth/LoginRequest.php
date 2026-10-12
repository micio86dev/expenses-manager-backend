<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Data\LoginData;
use App\Support\Auth\EmailKey;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $email = EmailKey::normalise($this->input('email'));

        if ($email !== null) {
            $this->merge(['email' => $email]);
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
