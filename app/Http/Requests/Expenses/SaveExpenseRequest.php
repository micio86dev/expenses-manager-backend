<?php

declare(strict_types=1);

namespace App\Http\Requests\Expenses;

use App\Data\ExpenseData;
use App\Rules\SelectableCategory;
use App\Support\DateRange;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Create and update share the same input. On update the expense may keep an archived category.
 */
final class SaveExpenseRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(SelectableCategory $selectable): array
    {
        $expense = $this->route('expense');

        return [
            'description' => ['required', 'string', 'min:1', 'max:255'],
            'amount_cents' => ['required', 'integer:strict', 'min:1', 'max:'.Money::MAX_AMOUNT_CENTS],
            'currency' => ['sometimes', 'string', 'regex:/^[A-Z]{3}$/'],
            'category_id' => [
                'required', 'string', 'uuid',
                is_string($expense) ? $selectable->keepingCurrentCategoryOf($expense) : $selectable,
            ],
            'spent_on' => ['required', 'string', 'date_format:'.DateRange::FORMAT],
        ];
    }

    public function toData(): ExpenseData
    {
        $input = $this->safe();

        return new ExpenseData(
            description: $input->string('description')->toString(),
            price: new Money(
                $input->integer('amount_cents'),
                $input->has('currency') ? $input->string('currency')->toString() : Money::DEFAULT_CURRENCY,
            ),
            categoryId: $input->string('category_id')->toString(),
            spentOn: DateRange::day($input->string('spent_on')->toString()),
        );
    }
}
