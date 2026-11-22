<?php

declare(strict_types=1);

namespace App\Contract\Dictionary;

use App\Contract\Dictionary\Traits\EnumValuesTrait;

enum VatType: string
{
    use EnumValuesTrait;

    case VatType1 = 'vat-type-1';
    case VatType2 = 'vat-type-2';
    case VatType3 = 'vat-type-3';
}
