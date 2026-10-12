<?php

declare(strict_types=1);

use App\Http\Requests\Auth\AuthenticatedRequest;
use Illuminate\Auth\AuthenticationException;

it('refuses to expose a user when the request is not authenticated', function (): void {
    $request = AuthenticatedRequest::create('/api/v1/auth/me');
    $request->setUserResolver(fn () => null);

    expect(fn () => $request->authenticatedUser())->toThrow(AuthenticationException::class);
});
