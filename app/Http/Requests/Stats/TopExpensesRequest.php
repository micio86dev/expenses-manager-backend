<?php

declare(strict_types=1);

namespace App\Http\Requests\Stats;

use App\Http\Requests\Concerns\DescribesStatsRange;
use Illuminate\Foundation\Http\FormRequest;

final class TopExpensesRequest extends FormRequest
{
    use DescribesStatsRange;

    public const int DEFAULT_LIMIT = 5;

    public const int MAX_LIMIT = 10;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->statsRules(),
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ];
    }

    /**
     * @return positive-int
     */
    public function limit(): int
    {
        return max(1, $this->safe()->integer('limit', self::DEFAULT_LIMIT));
    }
}
