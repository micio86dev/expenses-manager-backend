<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spectator\Spectator;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Every HTTP response in the api group is validated against the OpenAPI contract.
        Spectator::using('openapi.yaml');
    }
}
