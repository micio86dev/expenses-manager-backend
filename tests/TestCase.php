<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spectator\Spectator;

abstract class TestCase extends BaseTestCase
{
    /**
     * Rate limiters and the stats cache must not outlive a test. The limiter captures its store when the
     * application boots, so the store is forced before boot: a CACHE_STORE from the shell or the dev
     * container must not leak into the suite.
     */
    public function createApplication()
    {
        $this->forceEnvironment('CACHE_STORE', 'array');
        // The suite expects the default dev origin, whatever port the local stack was started on.
        $this->forceEnvironment('CORS_ALLOWED_ORIGINS', 'http://localhost:5173');

        return parent::createApplication();
    }

    private function forceEnvironment(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Every HTTP response in the api group is validated against the OpenAPI contract.
        Spectator::using('openapi.yaml');
    }
}
