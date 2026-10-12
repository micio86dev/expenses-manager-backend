<?php

declare(strict_types=1);

namespace App\Support\Owner;

use Illuminate\Database\Eloquent\Model;

/**
 * Makes a model multi-tenant ready: rows get `owner_id` from the {@see CurrentOwner}
 * on creation and every query is scoped to the current owner by {@see OwnerScope}.
 * The table must have a non-null `owner_id` column.
 *
 * @mixin Model
 */
trait OwnedByCurrentOwner
{
    public static function bootOwnedByCurrentOwner(): void
    {
        static::addGlobalScope(new OwnerScope);

        static::creating(static function (Model $model): void {
            if (array_key_exists(OwnerScope::COLUMN, $model->getAttributes())) {
                return;
            }

            $ownerId = app(CurrentOwner::class)->id();

            if ($ownerId === null) {
                throw MissingOwnerException::forModel($model::class);
            }

            $model->setAttribute(OwnerScope::COLUMN, $ownerId);
        });
    }
}
