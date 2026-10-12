<?php

declare(strict_types=1);

namespace App\Support\Owner;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts every query on an owned model to the current owner's rows, plus the shared
 * rows (NULL owner) of models that implement {@see IncludesUnownedRows}.
 * Fails closed: with no current owner the query matches nothing, shared rows included.
 *
 * @implements Scope<Model>
 */
final class OwnerScope implements Scope
{
    public const string COLUMN = 'owner_id';

    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $column = $model->qualifyColumn(self::COLUMN);
        $ownerId = app(CurrentOwner::class)->id();

        if ($ownerId === null) {
            $builder->whereIn($column, []);

            return;
        }

        if ($model instanceof IncludesUnownedRows) {
            $builder->where(static fn (Builder $query): Builder => $query->where($column, $ownerId)->orWhereNull($column));

            return;
        }

        $builder->where($column, $ownerId);
    }
}
