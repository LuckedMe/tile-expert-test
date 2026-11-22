<?php

declare(strict_types=1);

namespace App\DTOFactory\Entity;

use App\DTO\Entity\OrderInfoDTO;
use App\Entity\Order;

readonly class OrderInfoDTOFactory
{
    public function fromEntity(Order $order): OrderInfoDTO
    {
        return new OrderInfoDTO(
            id: $order->getId(),
        );
    }
}
