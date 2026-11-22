<?php

declare(strict_types=1);

namespace App\DTOFactory\Collection;

use App\ControllerRequest\Api\Order\GetOrderCollectionRequest;
use App\DTO\Collection\OrderCriteriaDTO;

class OrderCriteriaDTOFactory
{
    public function fromCollectionRequest(
        GetOrderCollectionRequest $request,
        array $context = [],
    ): OrderCriteriaDTO {
        return new OrderCriteriaDTO(
        );
    }
}
