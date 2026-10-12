<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginUser;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthResource;

final readonly class LoginController
{
    public function __invoke(LoginRequest $request, LoginUser $login): AuthResource
    {
        return new AuthResource($login($request->toData()));
    }
}
