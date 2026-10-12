<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Data\Stats\StatsQuery;
use App\Rules\WithinMaxYears;
use App\Support\DateRange;
use App\Support\Money;

/**
 * The `from`, `to` and `currency` query shared by every statistics endpoint.
 */
trait DescribesStatsRange
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function statsRules(): array
    {
        $date = 'date_format:'.DateRange::FORMAT;

        return [
            'from' => ['required', 'string', $date],
            'to' => ['bail', 'required', 'string', $date, 'after_or_equal:from', new WithinMaxYears('from', StatsQuery::MAX_YEARS)],
            'currency' => ['sometimes', 'string', 'regex:/^[A-Z]{3}$/'],
        ];
    }

    public function toStatsQuery(): StatsQuery
    {
        $input = $this->safe();

        return new StatsQuery(
            range: DateRange::fromStrings($input->string('from')->toString(), $input->string('to')->toString()),
            currency: $input->string('currency', Money::DEFAULT_CURRENCY)->toString(),
        );
    }
}
