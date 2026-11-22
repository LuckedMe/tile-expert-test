<?php

declare(strict_types=1);

namespace App\Entity;

use App\Contract\Entity\EntityInterface;
use App\Repository\OrderArticleRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'orders_article')]
#[ORM\Index(name: 'fk_orders_id', columns: ['orders_id'])]
#[ORM\Index(name: 'idx_article_id', columns: ['article_id'])]
#[ORM\Entity(repositoryClass: OrderArticleRepository::class)]
class OrderArticle implements EntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'orders_id', type: Types::INTEGER, nullable: false)]
    private ?int $ordersId = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'orders_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\Column(name: 'article_id', type: Types::INTEGER, nullable: true, options: ['comment' => 'Collection ID'])]
    private ?int $articleId = null;

    #[ORM\Column(name: 'amount', type: Types::DECIMAL, precision: 12, scale: 3, options: ['comment' => 'Number of articles in the unit of measure'])]
    private string $amount;

    #[ORM\Column(name: 'price', type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => 'Price at the time of order payment'])]
    private string $price;

    #[ORM\Column(name: 'price_eur', type: Types::DECIMAL, precision: 12, scale: 2, nullable: true, options: ['comment' => 'Order price in euros'])]
    private ?string $priceEur = null;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 3, nullable: true, options: ['comment' => 'Currency the price is set for'])]
    private ?string $currency = null;

    #[ORM\Column(name: 'measure', type: Types::STRING, length: 2, nullable: true, options: ['comment' => 'Unit of measure the price is set for'])]
    private ?string $measure = null;

    #[ORM\Column(name: 'delivery_time_min', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Minimum delivery time'])]
    private ?DateTimeImmutable $deliveryTimeMin = null;

    #[ORM\Column(name: 'delivery_time_max', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Maximum delivery time'])]
    private ?DateTimeImmutable $deliveryTimeMax = null;

    #[ORM\Column(name: 'weight', type: Types::DECIMAL, precision: 10, scale: 3, options: ['comment' => 'Package weight'])]
    private string $weight;

    #[ORM\Column(name: 'multiple_pallet', type: Types::SMALLINT, nullable: true, options: ['comment' => 'Pallet multiple: 1 - multiple of a package, 2 - multiple of a pallet, 3 - not less than a pallet'])]
    private ?int $multiplePallet = null;

    #[ORM\Column(name: 'packaging_count', type: Types::DECIMAL, precision: 10, scale: 3, options: ['comment' => 'Quantity multiple in which the product can be added to the order'])]
    private string $packagingCount;

    #[ORM\Column(name: 'pallet', type: Types::DECIMAL, precision: 10, scale: 3, options: ['comment' => 'Quantity per pallet at the time of the order'])]
    private string $pallet;

    #[ORM\Column(name: 'packaging', type: Types::DECIMAL, precision: 10, scale: 3, options: ['comment' => 'Quantity per package'])]
    private string $packaging;

    #[ORM\Column(name: 'swimming_pool', type: Types::BOOLEAN, options: ['default' => 0, 'comment' => 'Tile specifically for swimming pools'])]
    private bool $swimmingPool;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrdersId(): ?int
    {
        return $this->ordersId;
    }

    public function setOrdersId(?int $ordersId): static
    {
        $this->ordersId = $ordersId;

        return $this;
    }

    public function getArticleId(): ?int
    {
        return $this->articleId;
    }

    public function setArticleId(?int $articleId): static
    {
        $this->articleId = $articleId;

        return $this;
    }

    public function getAmount(): ?float
    {
        return isset($this->amount) ? (float) $this->amount : null;
    }

    public function setAmount(float|string $amount): static
    {
        $this->amount = (string) $amount;

        return $this;
    }

    public function getPrice(): ?float
    {
        return isset($this->price) ? (float) $this->price : null;
    }

    public function setPrice(float|string $price): static
    {
        $this->price = (string) $price;

        return $this;
    }

    public function getPriceEur(): ?float
    {
        return $this->priceEur === null ? null : (float) $this->priceEur;
    }

    public function setPriceEur(float|string|null $priceEur): static
    {
        $this->priceEur = $priceEur === null ? null : (string) $priceEur;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getMeasure(): ?string
    {
        return $this->measure;
    }

    public function setMeasure(?string $measure): static
    {
        $this->measure = $measure;

        return $this;
    }

    public function getDeliveryTimeMin(): ?DateTimeImmutable
    {
        return $this->deliveryTimeMin;
    }

    public function setDeliveryTimeMin(?DateTimeImmutable $deliveryTimeMin): static
    {
        $this->deliveryTimeMin = $deliveryTimeMin;

        return $this;
    }

    public function getDeliveryTimeMax(): ?DateTimeImmutable
    {
        return $this->deliveryTimeMax;
    }

    public function setDeliveryTimeMax(?DateTimeImmutable $deliveryTimeMax): static
    {
        $this->deliveryTimeMax = $deliveryTimeMax;

        return $this;
    }

    public function getWeight(): ?float
    {
        return isset($this->weight) ? (float) $this->weight : null;
    }

    public function setWeight(float|string $weight): static
    {
        $this->weight = (string) $weight;

        return $this;
    }

    public function getMultiplePallet(): ?int
    {
        return $this->multiplePallet;
    }

    public function setMultiplePallet(?int $multiplePallet): static
    {
        $this->multiplePallet = $multiplePallet;

        return $this;
    }

    public function getPackagingCount(): ?float
    {
        return isset($this->packagingCount) ? (float) $this->packagingCount : null;
    }

    public function setPackagingCount(float|string $packagingCount): static
    {
        $this->packagingCount = (string) $packagingCount;

        return $this;
    }

    public function getPallet(): ?float
    {
        return isset($this->pallet) ? (float) $this->pallet : null;
    }

    public function setPallet(float|string $pallet): static
    {
        $this->pallet = (string) $pallet;

        return $this;
    }

    public function getPackaging(): ?float
    {
        return isset($this->packaging) ? (float) $this->packaging : null;
    }

    public function setPackaging(float|string $packaging): static
    {
        $this->packaging = (string) $packaging;

        return $this;
    }

    public function isSwimmingPool(): ?bool
    {
        return $this->swimmingPool;
    }

    public function setSwimmingPool(bool $swimmingPool): static
    {
        $this->swimmingPool = $swimmingPool;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }
}
