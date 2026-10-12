<?php

declare(strict_types=1);

use App\Actions\Expenses\ListExpenses;
use App\Data\ExpenseFilter;
use App\Data\ExpensePage;
use App\Support\DateRange;
use Tests\Fakes\InMemoryExpenseRepository;

it('hands the filter to the repository and returns its page untouched', function (): void {
    $repository = new InMemoryExpenseRepository;
    $filter = new ExpenseFilter(DateRange::fromStrings('2026-10-01', '2026-10-31'), search: 'milk');

    $page = (new ListExpenses($repository))($filter);

    expect($page)->toBeInstanceOf(ExpensePage::class)
        ->and($repository->lastFilter)->toBe($filter)
        ->and($page->items)->toBe([])
        ->and($page->total)->toBe(0)
        ->and($page->lastPage)->toBe(1);
});
