<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\AuthenticatedRequest;
use App\Http\Resources\UserResource;

final readonly class ShowProfileController
{
    public function __invoke(AuthenticatedRequest $request): UserResource
    {
        return new UserResource($request->authenticatedUser());
    }
}
