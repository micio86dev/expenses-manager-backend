<?php

declare(strict_types=1);

use App\Support\Money;

it('holds an amount in minor units and a currency', function (): void {
    $money = new Money(1250, 'USD');

    expect($money->amountCents)->toBe(1250)->and($money->currency)->toBe('USD');
});

it('defaults to euro', function (): void {
    expect((new Money(1))->currency)->toBe('EUR');
});

it('rejects non-positive amounts', function (int $amount): void {
    expect(fn () => new Money($amount))->toThrow(InvalidArgumentException::class);
})->with([0, -1, PHP_INT_MIN]);

it('accepts the largest amount', function (): void {
    expect((new Money(PHP_INT_MAX))->amountCents)->toBe(PHP_INT_MAX);
});

it('rejects anything but an uppercase ISO 4217 shaped code', function (string $currency): void {
    expect(fn () => new Money(100, $currency))->toThrow(InvalidArgumentException::class);
})->with(['', 'EU', 'EURO', 'eur', 'E1R', ' EUR']);

it('compares by value', function (): void {
    expect((new Money(100, 'EUR'))->equals(new Money(100, 'EUR')))->toBeTrue()
        ->and((new Money(100, 'EUR'))->equals(new Money(101, 'EUR')))->toBeFalse()
        ->and((new Money(100, 'EUR'))->equals(new Money(100, 'USD')))->toBeFalse();
});

it('is immutable', function (): void {
    $money = new Money(100);

    expect(fn () => $money->amountCents = 5)->toThrow(Error::class);
});
