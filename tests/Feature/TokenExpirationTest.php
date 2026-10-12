<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Env;

function sanctumConfig(?string $expiration): array
{
    $repository = Env::getRepository();
    $previous = $repository->get('SANCTUM_EXPIRATION');

    $expiration === null ? $repository->clear('SANCTUM_EXPIRATION') : $repository->set('SANCTUM_EXPIRATION', $expiration);

    try {
        return require config_path('sanctum.php');
    } finally {
        $previous === null ? $repository->clear('SANCTUM_EXPIRATION') : $repository->set('SANCTUM_EXPIRATION', $previous);
    }
}

it('expires tokens after 30 days when nothing is configured', function (?string $value): void {
    expect(sanctumConfig($value)['expiration'])->toBe(43200);
})->with(['unset' => [null], 'empty' => ['']]);

it('honours an explicit token lifetime', function (): void {
    expect(sanctumConfig('60')['expiration'])->toBe(60);
});

it('prunes expired tokens every day', function (): void {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'sanctum:prune-expired'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 0 * * *')
        ->and((string) $events->first()->command)->toContain('--hours=24');
});
