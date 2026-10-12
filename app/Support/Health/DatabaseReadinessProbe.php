<?php

declare(strict_types=1);

namespace App\Support\Health;

use Illuminate\Database\DatabaseManager;
use RuntimeException;

/**
 * Ready when the default connection answers and the schema has been migrated.
 * Uses the schema builder, so it behaves the same on every supported database.
 */
final readonly class DatabaseReadinessProbe implements ReadinessProbe
{
    public function __construct(private DatabaseManager $database) {}

    public function check(): void
    {
        if (! $this->database->connection()->getSchemaBuilder()->hasTable('users')) {
            throw new RuntimeException('Database schema is not migrated.');
        }
    }
}
