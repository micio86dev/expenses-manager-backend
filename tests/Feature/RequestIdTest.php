<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

it('generates a request id when none is supplied', function (): void {
    $id = $this->getJson('/health')->headers->get('X-Request-Id');

    expect($id)->toBeString()->toBeUuid();
});

it('propagates a valid inbound request id', function (): void {
    $this->getJson('/health', ['X-Request-Id' => 'req-12345678'])
        ->assertHeader('X-Request-Id', 'req-12345678');
});

it('replaces an invalid inbound request id', function (string $inbound): void {
    $id = $this->getJson('/health', ['X-Request-Id' => $inbound])->headers->get('X-Request-Id');

    expect($id)->not->toBe($inbound)->toBeUuid();
})->with([
    'too short' => ['abc'],
    'whitespace' => ['has spaces in it'],
    'injection attempt' => ["abc\r\ndef-12345"],
    'too long' => [str_repeat('a', 65)],
]);

it('adds the request id to error responses', function (): void {
    $this->getJson('/api/v1/does-not-exist', ['X-Request-Id' => 'req-12345678'])
        ->assertNotFound()
        ->assertHeader('X-Request-Id', 'req-12345678');
});

it('writes JSON log lines carrying the request id', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'expenses-log-');
    config([
        'logging.default' => 'json',
        'logging.channels.json.handler_with.stream' => $file,
    ]);
    Route::get('/_probe/log', function () {
        Log::info('probe message', ['amount' => 12]);

        return response()->noContent();
    });
    Route::get('/_probe/boom', fn () => throw new RuntimeException('exploded'));

    $this->getJson('/_probe/log', ['X-Request-Id' => 'req-log-0001'])->assertNoContent();
    $this->getJson('/_probe/boom', ['X-Request-Id' => 'req-log-0002'])->assertStatus(500);

    $lines = array_map(
        static fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR),
        file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES),
    );
    unlink($file);

    $byMessage = collect($lines)->keyBy('message');

    expect($byMessage['probe message']['context'])->toMatchArray(['request_id' => 'req-log-0001', 'amount' => 12])
        ->and($byMessage['exploded']['context']['request_id'])->toBe('req-log-0002');
});

it('does not leak the request id into the next request context', function (): void {
    $this->getJson('/health', ['X-Request-Id' => 'req-12345678']);

    expect(Log::sharedContext())->not->toHaveKey('request_id');
});
