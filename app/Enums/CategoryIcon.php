<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The fixed icon set a category can use. Mirrors `CategoryIcon` in contracts/openapi.yaml.
 */
enum CategoryIcon: string
{
    case Cart = 'cart';
    case Home = 'home';
    case Car = 'car';
    case Bolt = 'bolt';
    case Heart = 'heart';
    case Plane = 'plane';
    case Coffee = 'coffee';
    case Gift = 'gift';
    case Book = 'book';
    case Briefcase = 'briefcase';
    case Tag = 'tag';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $icon): string => $icon->value, self::cases());
    }
}
