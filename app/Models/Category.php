<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryIcon;
use App\Support\Owner\IncludesUnownedRows;
use App\Support\Owner\OwnedByCurrentOwner;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * System categories are shared rows (`owner_id` NULL, `is_system` true, `translation_key` set);
 * custom categories belong to one owner. See {@see IncludesUnownedRows}.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string $name
 * @property string $color
 * @property CategoryIcon $icon
 * @property bool $is_system
 * @property string|null $translation_key
 * @property Carbon|null $archived_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['name', 'color', 'icon'])]
final class Category extends Model implements IncludesUnownedRows
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasUuids, OwnedByCurrentOwner;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['is_system' => false];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'icon' => CategoryIcon::class,
            'is_system' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * The name shown to the client: system categories are translated, custom ones are free text.
     */
    public function displayName(): string
    {
        if ($this->is_system && $this->translation_key !== null) {
            return __('categories.system.'.$this->translation_key);
        }

        return $this->name;
    }
}
