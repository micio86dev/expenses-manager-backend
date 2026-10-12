<?php

declare(strict_types=1);

namespace App\Data\Stats;

use App\Support\DateRange;
use App\Support\Money;
use InvalidArgumentException;

/**
 * What every statistic is computed over: a bounded range of days in one currency.
 * Totals are never mixed across currencies.
 */
final readonly class StatsQuery
{
    /** Longest range accepted, in calendar years; protects the database from unbounded scans. */
    public const int MAX_YEARS = 5;

    public function __construct(
        public DateRange $range,
        public string $currency = Money::DEFAULT_CURRENCY,
        bool $guardSpan = true,
    ) {
        if (! Money::isCurrencyCode($currency)) {
            throw new InvalidArgumentException('Currency must be an uppercase ISO 4217 code.');
        }

        if ($guardSpan && $range->exceedsYears(self::MAX_YEARS)) {
            throw new InvalidArgumentException('The range must not be longer than '.self::MAX_YEARS.' years.');
        }
    }

    /**
     * The same statistic over the period of equal length right before this one.
     * The span guard applies to the requested range only: a valid range always has a valid predecessor.
     */
    public function previous(): self
    {
        return new self($this->range->previous(), $this->currency, guardSpan: false);
    }
}
