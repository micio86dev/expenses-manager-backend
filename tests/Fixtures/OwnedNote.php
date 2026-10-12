<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Support\Owner\OwnedByCurrentOwner;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Test-only model proving {@see OwnedByCurrentOwner} before real domain tables exist.
 *
 * @property string $id
 * @property string $title
 * @property string $owner_id
 */
#[Fillable(['title'])]
final class OwnedNote extends Model
{
    use HasUuids, OwnedByCurrentOwner;

    protected $table = 'owned_notes';
}
