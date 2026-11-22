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
            hash: $order->getHash(),
            token: $order->getToken(),
            email: $order->getEmail(),
            vatType: $order->getVatType(),
            vatNumber: $order->getVatNumber(),
            taxNumber: $order->getTaxNumber(),
            paymentType: $order->getPayType(),
            locale: $order->getLocale(),
            currency: $order->getCurrency(),
            measure: $order->getMeasure(),
            name: $order->getName(),
            createdAt: $order->getCreateDate(),
            updatedAt: $order->getUpdateDate(),
        );
    }
}
