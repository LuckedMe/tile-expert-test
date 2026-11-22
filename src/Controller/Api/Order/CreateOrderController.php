<?php

declare(strict_types=1);

namespace App\Controller\Api\Order;

use App\Contract\Dictionary\ApiDocTag;
use App\ControllerRequest\Api\Order\CreateOrderRequest;
use App\Message\Command\Order\CreateOrderCommand;
use App\Service\MessageBusHandler;
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
    #[OA\Post(
        description: 'Creates an order together with its articles (the `articles` array, at least one item) in one transaction: '
            . 'if any article is invalid nothing is saved. The new order is added to the Manticore search index.',
        summary: 'Create an order',
    )]
    #[OA\Response(ref: '#/components/responses/error', response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed (e.g. the hash already exists)')]
    #[OA\Response(ref: '#/components/responses/invalid_json', response: Response::HTTP_BAD_REQUEST)]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: new Model(type: CreateOrderRequest::class),
            example: [
                'hash'         => '12345678901234567890123456780000',
                'token'        => '1234567890123456789012345678901212345678901234567890123456789000',
                'email'        => 'test@example.com',
                'vat_type'     => 'vat-type-1',
                'vat_number'   => '0123456789',
                'tax_number'   => '9876543210',
                'payment_type' => 'visa',
                'locale'       => 'en_GB',
                'currency'     => 'EUR',
                'measure'      => 'm',
                'name'         => 'Order name',
                'articles'     => [
                    [
                        'article_id'        => 5,
                        'amount'            => 12.5,
                        'price'             => 30.2,
                        'price_eur'         => 30.2,
                        'currency'          => 'EUR',
                        'measure'           => 'm',
                        'delivery_time_min' => '2026-12-01',
                        'delivery_time_max' => '2026-12-15',
                        'weight'            => 20,
                        'multiple_pallet'   => 1,
                        'packaging_count'   => 1,
                        'pallet'            => 50,
                        'packaging'         => 1.44,
                        'swimming_pool'     => false,
                    ],
                ],
            ],
        )
    )]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Order created')]
    #[OA\Tag(name: ApiDocTag::ORDERS)]
    public function __invoke(
        CreateOrderRequest $createRequest,
        MessageBusHandler $messageBusHandler,
    ): JsonResponse {
        $messageBusHandler->handleCommand(new CreateOrderCommand(data: $createRequest));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
