<?php

declare(strict_types=1);

namespace App\Support\Owner;

use LogicException;

final class MissingOwnerException extends LogicException
{
    public static function forModel(string $model): self
    {
        return new self("Cannot create {$model} without a current owner.");
    }
}
