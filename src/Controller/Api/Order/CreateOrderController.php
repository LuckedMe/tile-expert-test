<?php

declare(strict_types=1);

namespace App\Controller\Api\Order;

use App\Contract\Dictionary\ApiDocTag;
use App\ControllerRequest\Api\Order\CreateOrderRequest;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateOrderController extends AbstractController
{
    #[Route(
        path: '/api/orders',
        name: 'api.order.post',
        options: ['expose' => true],
        methods: ['POST']
    )]
    #[OA\Response(ref: '#/components/responses/error', response: Response::HTTP_UNPROCESSABLE_ENTITY)]
    #[OA\Response(ref: '#/components/responses/invalid_json', response: Response::HTTP_BAD_REQUEST)]
    #[OA\Response(ref: '#/components/responses/invalid_credentials', response: Response::HTTP_UNAUTHORIZED)]
    #[OA\Response(ref: '#/components/responses/forbidden', response: Response::HTTP_FORBIDDEN)]
    #[OA\Response(ref: '#/components/responses/not_found', response: Response::HTTP_NOT_FOUND)]
    #[OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: CreateOrderRequest::class)))]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'No content on success')]
    #[OA\Tag(name: ApiDocTag::ORDERS)]
    public function __invoke(
        CreateOrderRequest $request,
        // MessageBusHandler $messageBusHandler,
    ): JsonResponse {
        //        $dto = $messageBusHandler->handleCommand(
        //            new CreateOrderCommand(
        // //                fields: $request->fields,
        // //                context: $request->context,
        //            )
        //        );

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
