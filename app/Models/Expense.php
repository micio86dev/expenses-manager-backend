<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\ExpenseFilter;
use App\Observers\StatsInvalidationObserver;
use App\Support\DateRange;
use App\Support\Owner\OwnedByCurrentOwner;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Money is stored as integer minor units (`amount_cents`) plus an ISO 4217 currency.
 *
 * @property string $id
 * @property string $owner_id
 * @property string $description
 * @property int $amount_cents
 * @property string $currency
 * @property string $category_id
 * @property Carbon $spent_on
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['description', 'amount_cents', 'currency', 'category_id', 'spent_on'])]
#[ObservedBy(StatsInvalidationObserver::class)]
final class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, HasUuids, OwnedByCurrentOwner;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            // Stored as a plain Y-m-d string so range comparisons stay textual on every database.
            'spent_on' => 'date:Y-m-d',
        ];
    }

    /**
     * Applies every criterion of the filter; the one place that defines "which expenses".
     *
     * @param  Builder<Expense>  $query
     */
    public function scopeMatching(Builder $query, ExpenseFilter $filter): void
    {
        $query->spentWithin($filter->range);

        if ($filter->categoryId !== null) {
            $query->where('category_id', $filter->categoryId);
        }

        if ($filter->search !== null) {
            $query->whereLike('description', '%'.$filter->search.'%', caseSensitive: false);
        }
    }

    /**
     * Inclusive on both ends. `spent_on` is a plain Y-m-d string, so the comparison is textual.
     *
     * @param  Builder<Expense>  $query
     */
    public function scopeSpentWithin(Builder $query, DateRange $range): void
    {
        $query->whereBetween('spent_on', [$range->from->format(DateRange::FORMAT), $range->to->format(DateRange::FORMAT)]);
    }

    /**
     * @param  Builder<Expense>  $query
     */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('spent_on')->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
