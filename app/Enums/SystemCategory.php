<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The categories every owner gets. The backing value is the translation key stored in
 * `categories.translation_key`; the reference seeder upserts one row per case.
 */
enum SystemCategory: string
{
    case Groceries = 'groceries';
    case Dining = 'dining';
    case Transport = 'transport';
    case Housing = 'housing';
    case Utilities = 'utilities';
    case Health = 'health';
    case Leisure = 'leisure';
    case Other = 'other';

    public function translationKey(): string
    {
        return 'categories.system.'.$this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::Groceries => '#2F855A',
            self::Dining => '#C05621',
            self::Transport => '#2B6CB0',
            self::Housing => '#6B46C1',
            self::Utilities => '#B7791F',
            self::Health => '#C53030',
            self::Leisure => '#0B6B72',
            self::Other => '#4A5568',
        };
    }

    public function icon(): CategoryIcon
    {
        return match ($this) {
            self::Groceries => CategoryIcon::Cart,
            self::Dining => CategoryIcon::Coffee,
            self::Transport => CategoryIcon::Car,
            self::Housing => CategoryIcon::Home,
            self::Utilities => CategoryIcon::Bolt,
            self::Health => CategoryIcon::Heart,
            self::Leisure => CategoryIcon::Gift,
            self::Other => CategoryIcon::Other,
        };
    }
}
