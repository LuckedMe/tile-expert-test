<?php

declare(strict_types=1);

namespace App\Contract\Dictionary;

use App\Contract\Dictionary\Traits\EnumValuesTrait;

enum PaymentType: string
{
    use EnumValuesTrait;

    case Visa       = 'visa';
    case Mastercard = 'mastercard';
    case Paypal     = 'paypal';
}
