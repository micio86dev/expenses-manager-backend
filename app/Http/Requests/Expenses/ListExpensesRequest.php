<?php

declare(strict_types=1);

namespace App\Http\Requests\Expenses;

use App\Data\ExpenseFilter;
use App\Support\DateRange;
use Illuminate\Foundation\Http\FormRequest;

final class ListExpensesRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $date = 'date_format:'.DateRange::FORMAT;

        return [
            'from' => ['required', 'string', $date],
            'to' => ['required', 'string', $date, 'after_or_equal:from'],
            'category_id' => ['sometimes', 'string', 'uuid'],
            'q' => ['sometimes', 'string', 'min:1', 'max:100'],
            // The page cap keeps `(page - 1) * per_page` far below integer overflow.
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.ExpenseFilter::MAX_PER_PAGE],
        ];
    }

    public function toFilter(): ExpenseFilter
    {
        $input = $this->safe();

        return new ExpenseFilter(
            range: DateRange::fromStrings($input->string('from')->toString(), $input->string('to')->toString()),
            categoryId: $input->has('category_id') ? $input->string('category_id')->toString() : null,
            search: $input->has('q') ? $input->string('q')->toString() : null,
            page: $input->integer('page', 1),
            perPage: $input->integer('per_page', ExpenseFilter::DEFAULT_PER_PAGE),
        );
    }
}
