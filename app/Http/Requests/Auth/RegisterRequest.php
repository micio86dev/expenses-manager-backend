<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Data\RegisterData;
use App\Support\Locale\LocaleRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge(['email' => Str::lower(trim($email))]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(LocaleRegistry $locales): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:120'],
            // The unique rule checks the table by name; it is validation, not model access.
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'locale' => ['sometimes', 'string', Rule::in($locales->supported())],
        ];
    }

    public function toData(): RegisterData
    {
        $input = $this->safe();

        return new RegisterData(
            name: $input->string('name')->toString(),
            email: $input->string('email')->toString(),
            password: $input->string('password')->toString(),
            locale: $input->has('locale') ? $input->string('locale')->toString() : app()->getLocale(),
        );
    }
}
