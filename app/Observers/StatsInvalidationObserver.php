<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use App\Models\Expense;
use App\Support\Stats\StatsVersion;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * The single place that invalidates cached statistics: any change to an owner's expenses
 * or categories (names appear in the statistics) moves the owner to a new version.
 * Runs after the transaction commits, so a concurrent reader never caches pre-commit data
 * under the new version. Bulk query-builder writes bypass model events and must bump explicitly.
 */
final readonly class StatsInvalidationObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private StatsVersion $versions) {}

    public function saved(Expense|Category $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Expense|Category $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Expense|Category $model): void
    {
        $ownerId = $model->owner_id;

        // System categories are shared and read-only: they have no owner whose statistics could change.
        if ($ownerId !== null) {
            $this->versions->bump($ownerId);
        }
    }
}
