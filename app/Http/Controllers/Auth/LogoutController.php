<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LogoutUser;
use App\Http\Requests\Auth\AuthenticatedRequest;
use Illuminate\Http\Response;

final readonly class LogoutController
{
    public function __invoke(AuthenticatedRequest $request, LogoutUser $logout): Response
    {
        $logout($request->authenticatedUser());

        return new Response(status: 204);
    }
}
