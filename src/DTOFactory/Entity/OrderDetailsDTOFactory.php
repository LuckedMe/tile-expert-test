<?php

declare(strict_types=1);

namespace App\DTOFactory\Entity;

use App\DTO\Entity\OrderDetailsDTO;
use App\Entity\Order;

readonly class OrderDetailsDTOFactory
{
    public function fromEntity(Order $order): OrderDetailsDTO
    {
        return new OrderDetailsDTO(
            id: $order->getId(),
        );
    }
}
