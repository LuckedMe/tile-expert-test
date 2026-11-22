<?php

declare(strict_types=1);

namespace App\Contract\Dictionary;

use App\Contract\Dictionary\Traits\EnumValuesTrait;

enum OrderGroupBy: string
{
    use EnumValuesTrait;

    case Day   = 'day';
    case Month = 'month';
    case Year  = 'year';

    /** MySQL DATE_FORMAT() pattern, a trusted constant (never user input). */
    public function sqlFormat(): string
    {
        return match ($this) {
            self::Day   => '%Y-%m-%d',
            self::Month => '%Y-%m',
            self::Year  => '%Y',
        };
    }
}
