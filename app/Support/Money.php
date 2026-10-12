<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * An amount in minor currency units (cents) with its ISO 4217 code. Never a float.
 * Immutable; an expense amount is always strictly positive.
 */
final readonly class Money
{
    public const string DEFAULT_CURRENCY = 'EUR';

    public function __construct(
        public int $amountCents,
        public string $currency = self::DEFAULT_CURRENCY,
    ) {
        if ($amountCents < 1) {
            throw new InvalidArgumentException('Money must be a positive amount of minor units.');
        }

        if (! self::isCurrencyCode($currency)) {
            throw new InvalidArgumentException('Currency must be an uppercase ISO 4217 code.');
        }
    }

    public static function isCurrencyCode(string $currency): bool
    {
        return preg_match('/^[A-Z]{3}$/', $currency) === 1;
    }

    public function equals(self $other): bool
    {
        return $this->amountCents === $other->amountCents && $this->currency === $other->currency;
    }
}
