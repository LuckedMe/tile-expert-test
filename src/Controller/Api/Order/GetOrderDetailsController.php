<?php

declare(strict_types=1);

namespace App\Controller\Api\Order;

use App\Contract\Dictionary\ApiDocTag;
use App\DTO\Entity\OrderDetailsDTO;
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
    #[OA\Response(ref: '#/components/responses/error', response: Response::HTTP_UNPROCESSABLE_ENTITY)]
    #[OA\Response(ref: '#/components/responses/invalid_json', response: Response::HTTP_BAD_REQUEST)]
    #[OA\Response(ref: '#/components/responses/invalid_credentials', response: Response::HTTP_UNAUTHORIZED)]
    #[OA\Response(ref: '#/components/responses/forbidden', response: Response::HTTP_FORBIDDEN)]
    #[OA\Response(ref: '#/components/responses/not_found', response: Response::HTTP_NOT_FOUND)]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Return order details',
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
        //        MessageBusHandler $messageBusHandler,
    ): JsonResponse {
        /** @var OrderDetailsDTO $dto */
        // $dto = $messageBusHandler->handleQuery(new GetOrderDetailsQuery(id: $id));

        return $this->json(
            data: $dto,
            context: ['groups' => ['order_details']]
        );
    }
}
