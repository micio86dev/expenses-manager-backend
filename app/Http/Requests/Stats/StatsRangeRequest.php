<?php

declare(strict_types=1);

namespace App\Http\Requests\Stats;

use App\Http\Requests\Concerns\DescribesStatsRange;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Query of the summary and by-category endpoints.
 */
final class StatsRangeRequest extends FormRequest
{
    use DescribesStatsRange;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return $this->statsRules();
    }
}
