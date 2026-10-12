<?php

declare(strict_types=1);

namespace App\Support\Periods;

/**
 * A kind of calendar period. `Custom` is an arbitrary inclusive range chosen by the user.
 */
enum Period: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Custom = 'custom';

    /**
     * The periods a time series can be bucketed by.
     *
     * @return list<self>
     */
    public static function granularities(): array
    {
        return [self::Day, self::Week, self::Month];
    }
}
