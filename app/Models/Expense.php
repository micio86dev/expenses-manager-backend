<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Owner\OwnedByCurrentOwner;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
