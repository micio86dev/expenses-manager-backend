<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\UpdateProfile;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;

final readonly class UpdateProfileController
{
    public function __invoke(UpdateProfileRequest $request, UpdateProfile $update): UserResource
    {
        return new UserResource($update($request->authenticatedUser(), $request->toChanges()));
    }
}
