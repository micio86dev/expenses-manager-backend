<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use InvalidArgumentException;

/**
 * An inclusive range of calendar days, `from <= to`. Immutable.
 */
final readonly class DateRange
{
    public const string FORMAT = 'Y-m-d';

    public CarbonImmutable $from;

    public CarbonImmutable $to;

    public function __construct(CarbonImmutable $from, CarbonImmutable $to)
    {
        $this->from = $from->startOfDay();
        $this->to = $to->startOfDay();

        if ($this->from->greaterThan($this->to)) {
            throw new InvalidArgumentException('The range must not end before it starts.');
        }
    }

    /**
     * @param  string  $from  a `Y-m-d` date
     * @param  string  $to  a `Y-m-d` date
     */
    public static function fromStrings(string $from, string $to): self
    {
        return new self(self::day($from), self::day($to));
    }

    /**
     * The number of calendar days in the range, both ends included (at least 1).
     */
    public function days(): int
    {
        // Rounded because a day is not always 24 hours in a non-UTC timezone.
        return (int) round($this->from->diffInDays($this->to)) + 1;
    }

    /**
     * The range of the same length that ends the day before this one starts.
     */
    public function previous(): self
    {
        $to = $this->from->subDay();

        return new self($to->subDays($this->days() - 1), $to);
    }

    /**
     * Whether the range is longer than the given number of calendar years.
     * Five years from 2021-01-01 end on 2025-12-31; a leap day anchors to the last day of February.
     */
    public function exceedsYears(int $years): bool
    {
        return $this->to->greaterThan($this->from->addYearsNoOverflow($years)->subDay());
    }

    public function contains(CarbonImmutable $day): bool
    {
        $day = $day->startOfDay();

        return $day->greaterThanOrEqualTo($this->from) && $day->lessThanOrEqualTo($this->to);
    }

    /**
     * Parses one strict `Y-m-d` calendar day.
     */
    public static function day(string $value): CarbonImmutable
    {
        try {
            $day = CarbonImmutable::createFromFormat('!'.self::FORMAT, $value);
        } catch (InvalidFormatException) {
            $day = null;
        }

        // createFromFormat rolls impossible dates (2026-02-30) over instead of failing.
        if ($day === null || $day->format(self::FORMAT) !== $value) {
            throw new InvalidArgumentException("\"{$value}\" is not a valid ".self::FORMAT.' date.');
        }

        return $day;
    }
}
