<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Data\ProfileChanges;
use App\Http\Requests\Concerns\ResolvesAuthenticatedUser;
use App\Support\Locale\LocaleRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProfileRequest extends FormRequest
{
    use ResolvesAuthenticatedUser;

    /**
     * At least one of `name` and `locale` must be present.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(LocaleRegistry $locales): array
    {
        return [
            'name' => ['required_without:locale', 'string', 'min:1', 'max:120'],
            'locale' => ['required_without:name', 'string', Rule::in($locales->supported())],
        ];
    }

    public function toChanges(): ProfileChanges
    {
        $input = $this->safe();

        return new ProfileChanges(
            name: $input->has('name') ? $input->string('name')->toString() : null,
            locale: $input->has('locale') ? $input->string('locale')->toString() : null,
        );
    }
}
