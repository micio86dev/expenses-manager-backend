<?php

declare(strict_types=1);

namespace App\Http\Requests\Stats;

use App\Http\Requests\Concerns\DescribesStatsRange;
use App\Support\Periods\Period;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TimeseriesRequest extends FormRequest
{
    use DescribesStatsRange;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->statsRules(),
            'granularity' => ['sometimes', 'string', Rule::enum(Period::class)->only(Period::granularities())],
        ];
    }

    public function granularity(): Period
    {
        return Period::tryFrom($this->safe()->string('granularity', Period::Day->value)->toString()) ?? Period::Day;
    }
}
