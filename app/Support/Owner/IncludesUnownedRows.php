<?php

declare(strict_types=1);

namespace App\Support\Owner;

/**
 * Marker for owned models that also expose shared rows with a NULL `owner_id`
 * (for example system categories). {@see OwnerScope} then matches
 * `owner_id = current OR owner_id IS NULL`; the rule lives in that one place.
 */
interface IncludesUnownedRows {}
