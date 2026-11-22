<?php

declare(strict_types=1);

namespace App\Contract\Dictionary;

use App\Contract\Dictionary\Traits\EnumValuesTrait;

enum CurrencySymbol: string
{
    use EnumValuesTrait;

    case USD = '$';
    case EUR = '€';
}
