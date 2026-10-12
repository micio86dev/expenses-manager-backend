<?php

declare(strict_types=1);

namespace App\Rules;

use App\Repositories\Contracts\CategoryRepository;
use App\Repositories\Contracts\ExpenseRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A category an expense may be filed under: visible to the owner (own or system) and not archived.
 * Archiving only blocks NEW selections, so when updating an expense it may keep the archived
 * category it already has (see {@see self::keepingCurrentCategoryOf()}).
 */
final readonly class SelectableCategory implements ValidationRule
{
    public function __construct(
        private CategoryRepository $categories,
        private ExpenseRepository $expenses,
        private ?string $expenseId = null,
    ) {}

    public function keepingCurrentCategoryOf(string $expenseId): self
    {
        return new self($this->categories, $this->expenses, $expenseId);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! $this->categories->exists($value, includeArchived: true)) {
            $fail('categories.errors.unavailable')->translate();

            return;
        }

        if ($this->categories->exists($value, includeArchived: false)) {
            return;
        }

        if ($this->expenseId === null || $this->expenses->categoryIdOf($this->expenseId) !== $value) {
            $fail('categories.errors.unavailable')->translate();
        }
    }
}
