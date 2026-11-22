<?php

declare(strict_types=1);

namespace App\EntityFactory\Order;

use App\Contract\EntityFactory\OrderEntityFactoryInterface;
use App\Entity\Order;
use Symfony\Component\HttpFoundation\Request;

use function is_array;

final readonly class OrderArrayEntityFactory implements OrderEntityFactoryInterface
{
    public function __construct(
        private OrderHttpRequestEntityFactory $orderHttpRequestEntityFactory,
    ) {
    }

    public function supports(mixed $data): bool
    {
        return is_array($data);
    }

    public function create(/** @var array $data */ mixed $data): Order
    {
        $request = Request::create(uri: '', method: Request::METHOD_POST, parameters: $data);

        return $this->orderHttpRequestEntityFactory->create($request);
    }
}
