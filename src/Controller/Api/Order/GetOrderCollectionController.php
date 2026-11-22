<?php

declare(strict_types=1);

namespace App\Controller\Api\Order;

use App\Contract\Dictionary\ApiDocTag;
use App\ControllerRequest\Api\Order\GetOrderCollectionRequest;
use App\DTO\Collection\OrderInfoCollectionDTO;
use App\DTOFactory\Collection\OrderCriteriaDTOFactory;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetOrderCollectionController extends AbstractController
{
    #[Route(
        path: '/api/orders',
        name: 'api.order.collection',
        options: ['expose' => true],
        methods: ['GET']
    )]
    #[OA\Response(ref: '#/components/responses/error', response: Response::HTTP_UNPROCESSABLE_ENTITY)]
    #[OA\Response(ref: '#/components/responses/invalid_json', response: Response::HTTP_BAD_REQUEST)]
    #[OA\Response(ref: '#/components/responses/invalid_credentials', response: Response::HTTP_UNAUTHORIZED)]
    #[OA\Response(ref: '#/components/responses/forbidden', response: Response::HTTP_FORBIDDEN)]
    #[OA\Response(ref: '#/components/responses/not_found', response: Response::HTTP_NOT_FOUND)]
    // custom
    #[OA\Parameter(ref: '#/components/parameters/page')]
    #[OA\Parameter(ref: '#/components/parameters/per_page')]
    #[OA\Parameter(ref: '#/components/parameters/query')]
    #[OA\Parameter(ref: '#/components/parameters/start_day')]
    #[OA\Parameter(ref: '#/components/parameters/end_day')]
    // #[OA\Parameter(ref: '#/components/parameters/sort')]
    #[OA\Parameter(
        name: 'sort',
        in: 'query',
        required: false,
        schema: new OA\Schema(
            type: 'string',
            default: 'created_at asc'
        ),
        examples: [
            new OA\Examples(example: 'created_at asc', summary: 'Sort by created_at asc', value: 'created_at asc'),
            new OA\Examples(example: 'created_at desc', summary: 'Sort by created_at desc', value: 'created_at desc'),
        ]
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns collection',
        content: new OA\JsonContent(
            ref: new Model(
                type: OrderInfoCollectionDTO::class,
                groups: ['collection_meta', 'order_collection'],
            )
        ),
    )]
    #[OA\Tag(name: ApiDocTag::ORDERS)]
    public function __invoke(
        //        MessageBusHandler $messageBusHandler,
        OrderCriteriaDTOFactory $factory,
        GetOrderCollectionRequest $request,
    ): JsonResponse {
        $dto = null;
        //        $dto = $messageBusHandler->handleQuery(
        //            new GetOrderCollectionQuery(
        //                criteria: $factory->fromCollectionRequest($request)
        //            ));

        return $this->json(
            data: $dto,
            context: ['groups' => ['collection_meta', 'order_collection']]
        );
    }
}
