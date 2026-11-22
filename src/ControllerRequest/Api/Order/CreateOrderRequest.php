<?php

declare(strict_types=1);

namespace App\ControllerRequest\Api\Order;

use App\Contract\ControllerRequest\ControllerRequestInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateOrderRequest implements ControllerRequestInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[OA\Property(description: 'Order email', type: 'string')]
        public ?string $email = null,
    ) {
    }
}
