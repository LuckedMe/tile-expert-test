<?php

declare(strict_types=1);

namespace App\Contract\Dictionary;

use App\Contract\Dictionary\Traits\EnumValuesTrait;

enum OrderStatus: string
{
    use EnumValuesTrait;

    case Page        = 'page';
    case Article     = 'article';
    case Created     = 'created';
    case Canceled    = 'canceled';
    case Confirmed   = 'confirmed';
    case Paid        = 'paid';
    case PickupReady = 'pickup_ready';
}
