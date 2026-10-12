<?php

declare(strict_types=1);

it('requires authentication on every expense endpoint', function (string $method, string $uri): void {
    $this->json($method, $uri)->assertUnauthorized()->assertValidResponse(401);
})->with([
    ['GET', '/api/v1/expenses'],
    ['POST', '/api/v1/expenses'],
    ['GET', '/api/v1/expenses/0199c0a6-5b3b-7000-8000-000000000001'],
    ['PUT', '/api/v1/expenses/0199c0a6-5b3b-7000-8000-000000000001'],
    ['DELETE', '/api/v1/expenses/0199c0a6-5b3b-7000-8000-000000000001'],
]);
