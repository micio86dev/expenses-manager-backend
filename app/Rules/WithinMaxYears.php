<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\DateRange;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

/**
 * The end date of a range that must not be longer than a number of years. Malformed or
 * reversed ranges are left to the format and ordering rules, which report them.
 */
final class WithinMaxYears implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(
        private readonly string $startField,
        private readonly int $years,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $start = $this->data[$this->startField] ?? null;

        if (! is_string($start) || ! is_string($value)) {
            return;
        }

        try {
            $range = DateRange::fromStrings($start, $value);
        } catch (InvalidArgumentException) {
            return;
        }

        if ($range->exceedsYears($this->years)) {
            $fail('stats.errors.range_too_long')->translate(['years' => $this->years]);
        }
    }
}
