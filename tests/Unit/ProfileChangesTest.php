<?php

declare(strict_types=1);

use App\Data\ProfileChanges;

it('lists only the attributes that were provided', function (?string $name, ?string $locale, array $expected): void {
    expect((new ProfileChanges($name, $locale))->attributes())->toBe($expected);
})->with([
    'nothing' => [null, null, []],
    'name only' => ['Ada', null, ['name' => 'Ada']],
    'locale only' => [null, 'en', ['locale' => 'en']],
    'both' => ['Ada', 'en', ['name' => 'Ada', 'locale' => 'en']],
]);
