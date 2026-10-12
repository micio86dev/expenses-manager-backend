<?php

declare(strict_types=1);

namespace App\Data;

/**
 * A partial profile update: null means "leave unchanged".
 */
final readonly class ProfileChanges
{
    public function __construct(
        public ?string $name = null,
        public ?string $locale = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_filter(
            ['name' => $this->name, 'locale' => $this->locale],
            static fn (?string $value): bool => $value !== null,
        );
    }
}
