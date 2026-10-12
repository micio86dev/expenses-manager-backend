<?php

declare(strict_types=1);

namespace App\Data;

use App\Support\DateRange;
use InvalidArgumentException;

/**
 * Everything the expense listing can be narrowed by. The single description of "which
 * expenses" for this and future readers (statistics reuse it through Expense::matching()).
 */
final readonly class ExpenseFilter
{
    public const int DEFAULT_PER_PAGE = 50;

    public const int MAX_PER_PAGE = 100;

    public ?string $search;

    public function __construct(
        public DateRange $range,
        public ?string $categoryId = null,
        ?string $search = null,
        public int $page = 1,
        public int $perPage = self::DEFAULT_PER_PAGE,
    ) {
        if ($page < 1) {
            throw new InvalidArgumentException('The page must be at least 1.');
        }

        if ($perPage < 1 || $perPage > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException('The page size must be between 1 and '.self::MAX_PER_PAGE.'.');
        }

        $this->search = $search === '' ? null : $search;
    }
}
