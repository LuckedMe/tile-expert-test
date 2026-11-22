<?php

declare(strict_types=1);

namespace App\Contract\Dictionary;

use App\Contract\Dictionary\Traits\EnumValuesTrait;

enum Currency: string
{
    use EnumValuesTrait;

    case USD = 'USD';
    case EUR = 'EUR';
}
