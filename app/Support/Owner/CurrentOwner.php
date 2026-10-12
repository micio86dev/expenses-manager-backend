<?php

declare(strict_types=1);

namespace App\Support\Owner;

/**
 * Identifies who owns the rows the current request may see and create.
 * Today the owner is the authenticated user; a tenant implementation is a single binding change.
 */
interface CurrentOwner
{
    /**
     * @return non-empty-string|null null when nobody is authenticated
     */
    public function id(): ?string;
}
