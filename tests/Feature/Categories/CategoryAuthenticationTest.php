<?php

declare(strict_types=1);

it('requires authentication on every category endpoint', function (string $method, string $uri): void {
    $this->json($method, $uri)->assertUnauthorized()->assertValidResponse(401);
})->with([
    ['GET', '/api/v1/categories'],
    ['POST', '/api/v1/categories'],
    ['GET', '/api/v1/categories/0199c0a6-5b3b-7000-8000-000000000001'],
    ['PUT', '/api/v1/categories/0199c0a6-5b3b-7000-8000-000000000001'],
    ['DELETE', '/api/v1/categories/0199c0a6-5b3b-7000-8000-000000000001'],
    ['POST', '/api/v1/categories/0199c0a6-5b3b-7000-8000-000000000001/archive'],
    ['POST', '/api/v1/categories/0199c0a6-5b3b-7000-8000-000000000001/unarchive'],
]);
