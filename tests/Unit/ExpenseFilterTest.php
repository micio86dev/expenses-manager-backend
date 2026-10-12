<?php

declare(strict_types=1);

use App\Data\ExpenseFilter;
use App\Support\DateRange;

function filterRange(): DateRange
{
    return DateRange::fromStrings('2026-10-01', '2026-10-31');
}

it('applies the documented defaults', function (): void {
    $filter = new ExpenseFilter(filterRange());

    expect($filter->categoryId)->toBeNull()
        ->and($filter->search)->toBeNull()
        ->and($filter->page)->toBe(1)
        ->and($filter->perPage)->toBe(50);
});

it('keeps what it is given', function (): void {
    $filter = new ExpenseFilter(filterRange(), categoryId: 'c1', search: 'milk', page: 3, perPage: 100);

    expect($filter->categoryId)->toBe('c1')->and($filter->search)->toBe('milk')->and($filter->page)->toBe(3)->and($filter->perPage)->toBe(100);
});

it('rejects an out-of-bounds page or page size', function (int $page, int $perPage): void {
    expect(fn () => new ExpenseFilter(filterRange(), page: $page, perPage: $perPage))->toThrow(InvalidArgumentException::class);
})->with([
    'page zero' => [0, 50],
    'negative page' => [-1, 50],
    'page size zero' => [1, 0],
    'page size over the cap' => [1, 101],
]);

it('treats an empty search as no search', function (): void {
    expect((new ExpenseFilter(filterRange(), search: ''))->search)->toBeNull();
});
