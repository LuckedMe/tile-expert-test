<?php

declare(strict_types=1);

namespace App\EntityFactory\Order;

use App\ArgumentResolver\ControllerRequestResolver;
use App\Contract\EntityFactory\OrderEntityFactoryInterface;
use App\ControllerRequest\Api\Order\CreateOrderRequest;
use App\Entity\Order;
use App\Exception\Validation\UnprocessableEntityException;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final readonly class OrderHttpRequestEntityFactory implements OrderEntityFactoryInterface
{
    public function __construct(
        private OrderEntityFactory $orderEntityFactory,
        private ControllerRequestResolver $controllerRequestResolver,
    ) {
    }

    public function supports(mixed $data): bool
    {
        return $data instanceof Request;
    }

    public function create(/** @var Request $data */ mixed $data): Order
    {
        try {
            $createRequest = $this
                ->controllerRequestResolver
                ->createControllerRequest($data, CreateOrderRequest::class);
        } catch (ExceptionInterface|ReflectionException $e) {
            throw new UnprocessableEntityException(message: $e->getMessage(), previous: $e);
        }

        return $this->orderEntityFactory->create($createRequest);
    }
}
