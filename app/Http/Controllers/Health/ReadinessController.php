<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Actions\Health\CheckReadiness;
use Illuminate\Http\JsonResponse;

final readonly class ReadinessController
{
    public function __invoke(CheckReadiness $check): JsonResponse
    {
        $check();

        return new JsonResponse(['status' => 'ready']);
    }
}
