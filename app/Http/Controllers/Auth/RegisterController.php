<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUser;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthResource;
use Illuminate\Http\JsonResponse;

final readonly class RegisterController
{
    public function __invoke(RegisterRequest $request, RegisterUser $register): JsonResponse
    {
        return (new AuthResource($register($request->toData())))
            ->response()
            ->setStatusCode(201);
    }
}
