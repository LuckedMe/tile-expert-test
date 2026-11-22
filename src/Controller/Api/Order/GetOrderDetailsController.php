<?php

declare(strict_types=1);

namespace App\Controller\Api\Order;

use App\Contract\Dictionary\ApiDocTag;
use App\DTO\Entity\OrderDetailsDTO;
use App\Message\Query\Order\GetOrderDetailsQuery;
use App\Service\MessageBusHandler;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

class GetOrderDetailsController extends AbstractController
{
    #[Route(
        path: '/api/orders/{id}',
        name: 'api.order.details',
        requirements: ['id' => Requirement::POSITIVE_INT],
        options: ['expose' => true],
        methods: ['GET']
    )]
    #[OA\Get(
        description: 'Returns one order together with its articles.',
        summary: 'Get order details',
    )]
    #[OA\Parameter(name: 'id', description: 'Order id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1), example: 1)]
    #[OA\Response(ref: '#/components/responses/error', response: Response::HTTP_UNPROCESSABLE_ENTITY)]
    #[OA\Response(ref: '#/components/responses/not_found', response: Response::HTTP_NOT_FOUND, description: 'Order not found')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Order with articles',
        content: new OA\JsonContent(
            ref: new Model(
                type: OrderDetailsDTO::class,
                groups: ['order_details'],
            )
        )
    )]
    #[OA\Tag(name: ApiDocTag::ORDERS)]
    public function __invoke(
        int $id,
        MessageBusHandler $messageBusHandler,
    ): JsonResponse {
        /** @var OrderDetailsDTO $dto */
        $dto = $messageBusHandler->handleQuery(new GetOrderDetailsQuery(id: $id));

        return $this->json(
            data: $dto,
            context: ['groups' => ['order_details']]
        );
    }
}
