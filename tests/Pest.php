<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spectator\Spectator;
use Spectator\Validation\ResponseValidator;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Unit');

/**
 * Validates a response that Spectator's middleware cannot see (routes outside the
 * `api` group, such as the health probes) against the OpenAPI contract.
 */
function assertMatchesContract(TestResponse $response, string $method, string $path): void
{
    $spec = Spectator::resolve();
    $operation = $spec->paths[$path]->{strtolower($method)};

    ResponseValidator::validate($response->baseResponse, $operation, $spec->openapi);
}
