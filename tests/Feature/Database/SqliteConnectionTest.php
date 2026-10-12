<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

it('waits for a busy sqlite database instead of failing immediately', function (): void {
    expect(Config::get('database.connections.sqlite.busy_timeout'))->toBe(5000);
});

it('uses write-ahead logging so readers do not block the writer', function (): void {
    expect(Config::get('database.connections.sqlite.journal_mode'))->toBe('WAL');
});
