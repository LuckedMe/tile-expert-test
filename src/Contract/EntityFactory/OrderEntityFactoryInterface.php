<?php

declare(strict_types=1);

namespace App\Contract\EntityFactory;

use App\Entity\Order;

/**
 * Creates an order from one particular source of the command (http request, CreateOrderRequest, ...).
 */
interface OrderEntityFactoryInterface
{
    public function supports(mixed $data): bool;

    public function create(mixed $data): Order;
}
