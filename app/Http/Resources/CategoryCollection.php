<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

final class CategoryCollection extends ResourceCollection
{
    public $collects = CategoryResource::class;
}
