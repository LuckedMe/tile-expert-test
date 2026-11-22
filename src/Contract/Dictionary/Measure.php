<?php

declare(strict_types=1);

namespace App\Contract\Dictionary;

use App\Contract\Dictionary\Traits\EnumValuesTrait;

enum Measure: string
{
    use EnumValuesTrait;

    case M  = 'm';
    case MM = 'mm';
    case SM = 'sm';
    case KG = 'kg';
}
