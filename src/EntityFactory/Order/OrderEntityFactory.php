<?php

declare(strict_types=1);

namespace App\EntityFactory\Order;

use App\Contract\Dictionary\Currency;
use App\Contract\Dictionary\Measure;
use App\Contract\Dictionary\OrderStatus;
use App\Contract\Dictionary\PaymentType;
use App\Contract\Dictionary\VatType;
use App\Contract\EntityFactory\OrderEntityFactoryInterface;
use App\ControllerRequest\Api\Order\CreateOrderRequest;
use App\Entity\Order;
use DateTimeImmutable;

final readonly class OrderEntityFactory implements OrderEntityFactoryInterface
{
    public function supports(mixed $data): bool
    {
        return $data instanceof CreateOrderRequest;
    }

    public function create(/** @var CreateOrderRequest $data */ mixed $data): Order
    {
        return new Order()
            ->setStatus(OrderStatus::Created)
            ->setHash($data->hash)
            ->setToken($data->token)
            ->setEmail($data->email)

            ->setVatType(VatType::tryFrom($data->vatType))
            ->setVatNumber($data->vatNumber)
            ->setTaxNumber($data->taxNumber)
            ->setPayType(PaymentType::tryFrom($data->paymentType))
            ->setLocale($data->locale)
            ->setCurrency(Currency::tryFrom($data->currency))
            ->setMeasure(Measure::tryFrom($data->measure))
            ->setName($data->name)

            ->setCreateDate(new DateTimeImmutable())
            ->setStep(true);
    }
}
