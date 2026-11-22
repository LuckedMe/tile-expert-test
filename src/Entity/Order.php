<?php

declare(strict_types=1);

namespace App\Entity;

use App\Contract\Dictionary\Currency;
use App\Contract\Dictionary\Measure;
use App\Contract\Dictionary\OrderStatus;
use App\Contract\Dictionary\PaymentType;
use App\Contract\Dictionary\VatType;
use App\Contract\Entity\EntityInterface;
use App\Repository\OrderRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'orders')]
#[ORM\Index(name: 'idx_delivery_country', columns: ['delivery_country'])]
#[ORM\Index(name: 'idx_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_create_date', columns: ['create_date'])]
#[ORM\Index(name: 'idx_create_date_status', columns: ['create_date', 'status'])]
#[ORM\Index(name: 'idx_hash', columns: ['hash'])]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
class Order implements EntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: OrderArticle::class, mappedBy: 'order', orphanRemoval: true)]
    private Collection $articles;

    #[ORM\Column(name: 'hash', type: Types::STRING, length: 32, options: ['comment' => 'Order hash'])]
    private string $hash;

    #[ORM\Column(name: 'user_id', type: Types::INTEGER, nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(name: 'token', type: Types::STRING, length: 64, options: ['comment' => 'Unique user hash'])]
    private string $token;

    #[ORM\Column(name: 'number', type: Types::STRING, length: 10, nullable: true, options: ['comment' => 'Order number'])]
    private ?string $number = null;

    #[ORM\Column(name: 'status', type: Types::ENUM, nullable: false, enumType: OrderStatus::class, options: ['default' => OrderStatus::Created->value, 'comment' => 'Order status'])]
    private OrderStatus $status = OrderStatus::Created;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 100, nullable: true, options: ['comment' => 'Contact email'])]
    private ?string $email = null;

    #[ORM\Column(name: 'vat_type', type: Types::ENUM, nullable: false, enumType: VatType::class, options: ['default' => VatType::VatType1->value, 'comment' => 'Private person or VAT payer'])]
    private VatType $vatType = VatType::VatType1;

    #[ORM\Column(name: 'vat_number', type: Types::STRING, length: 100, nullable: true, options: ['comment' => 'VAT number'])]
    private ?string $vatNumber = null;

    #[ORM\Column(name: 'tax_number', type: Types::STRING, length: 50, nullable: true, options: ['comment' => 'Individual taxpayer identification number'])]
    private ?string $taxNumber = null;

    #[ORM\Column(name: 'discount', type: Types::SMALLINT, nullable: true, options: ['comment' => 'Discount percentage'])]
    private ?int $discount = null;

    #[ORM\Column(name: 'delivery', type: Types::FLOAT, nullable: true, options: ['comment' => 'Delivery cost'])]
    private ?float $delivery = null;

    #[ORM\Column(name: 'delivery_type', type: Types::SMALLINT, nullable: true, options: ['default' => 0, 'comment' => 'Delivery type: 0 - client address, 1 - warehouse address'])]
    private ?int $deliveryType = null;

    #[ORM\Column(name: 'delivery_time_min', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Minimum delivery time'])]
    private ?DateTimeImmutable $deliveryTimeMin = null;

    #[ORM\Column(name: 'delivery_time_max', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Maximum delivery time'])]
    private ?DateTimeImmutable $deliveryTimeMax = null;

    #[ORM\Column(name: 'delivery_time_confirm_min', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Minimum delivery time confirmed by the manufacturer'])]
    private ?DateTimeImmutable $deliveryTimeConfirmMin = null;

    #[ORM\Column(name: 'delivery_time_confirm_max', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Maximum delivery time confirmed by the manufacturer'])]
    private ?DateTimeImmutable $deliveryTimeConfirmMax = null;

    #[ORM\Column(name: 'delivery_time_fast_pay_min', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Minimum delivery time'])]
    private ?DateTimeImmutable $deliveryTimeFastPayMin = null;

    #[ORM\Column(name: 'delivery_time_fast_pay_max', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Maximum delivery time'])]
    private ?DateTimeImmutable $deliveryTimeFastPayMax = null;

    #[ORM\Column(name: 'delivery_old_time_min', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Previous minimum delivery time'])]
    private ?DateTimeImmutable $deliveryOldTimeMin = null;

    #[ORM\Column(name: 'delivery_old_time_max', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Previous maximum delivery time'])]
    private ?DateTimeImmutable $deliveryOldTimeMax = null;

    #[ORM\Column(name: 'delivery_index', type: Types::STRING, length: 20, nullable: true)]
    private ?string $deliveryIndex = null;

    #[ORM\Column(name: 'delivery_country', type: Types::INTEGER, nullable: true)]
    private ?int $deliveryCountry = null;

    #[ORM\Column(name: 'delivery_region', type: Types::STRING, length: 50, nullable: true)]
    private ?string $deliveryRegion = null;

    #[ORM\Column(name: 'delivery_city', type: Types::STRING, length: 200, nullable: true)]
    private ?string $deliveryCity = null;

    #[ORM\Column(name: 'delivery_address', type: Types::STRING, length: 300, nullable: true)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(name: 'delivery_building', type: Types::STRING, length: 200, nullable: true)]
    private ?string $deliveryBuilding = null;

    #[ORM\Column(name: 'delivery_phone_code', type: Types::STRING, length: 20, nullable: true)]
    private ?string $deliveryPhoneCode = null;

    #[ORM\Column(name: 'delivery_phone', type: Types::STRING, length: 20, nullable: true)]
    private ?string $deliveryPhone = null;

    #[ORM\Column(name: 'sex', type: Types::SMALLINT, nullable: true, options: ['comment' => 'Client gender'])]
    private ?int $sex = null;

    #[ORM\Column(name: 'client_name', type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'Client first name'])]
    private ?string $clientName = null;

    #[ORM\Column(name: 'client_surname', type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'Client last name'])]
    private ?string $clientSurname = null;

    #[ORM\Column(name: 'company_name', type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'Company name'])]
    private ?string $companyName = null;

    #[ORM\Column(name: 'pay_type', type: Types::ENUM, nullable: false, enumType: PaymentType::class, options: ['default' => PaymentType::Visa->value, 'comment' => 'Selected payment type'])]
    private PaymentType $payType = PaymentType::Visa;

    #[ORM\Column(name: 'pay_date_execution', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'Date until which the current order price is valid'])]
    private ?DateTimeImmutable $payDateExecution = null;

    #[ORM\Column(name: 'offset_date', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'Date of shift of the estimated delivery calculation'])]
    private ?DateTimeImmutable $offsetDate = null;

    #[ORM\Column(name: 'offset_reason', type: Types::SMALLINT, nullable: true, options: ['comment' => 'Reason type for the deadline shift: 1 - factory holidays, 2 - factory is clarifying production times, 3 - other'])]
    private ?int $offsetReason = null;

    #[ORM\Column(name: 'proposed_date', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'Estimated delivery date'])]
    private ?DateTimeImmutable $proposedDate = null;

    #[ORM\Column(name: 'ship_date', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'Estimated shipping date'])]
    private ?DateTimeImmutable $shipDate = null;

    #[ORM\Column(name: 'tracking_number', type: Types::STRING, length: 50, nullable: true, options: ['comment' => 'Tracking number'])]
    private ?string $trackingNumber = null;

    #[ORM\Column(name: 'manager_name', type: Types::STRING, length: 20, nullable: true, options: ['comment' => 'Name of the manager handling the order'])]
    private ?string $managerName = null;

    #[ORM\Column(name: 'manager_email', type: Types::STRING, length: 30, nullable: true, options: ['comment' => 'Email of the manager handling the order'])]
    private ?string $managerEmail = null;

    #[ORM\Column(name: 'manager_phone', type: Types::STRING, length: 20, nullable: true, options: ['comment' => 'Phone of the manager handling the order'])]
    private ?string $managerPhone = null;

    #[ORM\Column(name: 'carrier_name', type: Types::STRING, length: 50, nullable: true, options: ['comment' => 'Carrier company name'])]
    private ?string $carrierName = null;

    #[ORM\Column(name: 'carrier_contact_data', type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'Carrier company contact details'])]
    private ?string $carrierContactData = null;

    #[ORM\Column(name: 'locale', type: Types::STRING, length: 5, options: ['comment' => 'Locale the order was placed from'])]
    private string $locale;

    #[ORM\Column(name: 'cur_rate', type: Types::FLOAT, nullable: true, options: ['default' => 1, 'comment' => 'Exchange rate at the time of payment'])]
    private ?float $curRate = null;

    #[ORM\Column(name: 'currency', type: Types::ENUM, nullable: false, enumType: Currency::class, options: ['default' => Currency::EUR->value, 'comment' => 'Currency the order was placed in'])]
    private Currency $currency = Currency::EUR;

    #[ORM\Column(name: 'measure', type: Types::ENUM, nullable: false, enumType: Measure::class, options: ['default' => Measure::M->value, 'comment' => 'Unit of measure the order was placed in'])]
    private Measure $measure = Measure::M;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 200, options: ['comment' => 'Order name'])]
    private string $name;

    #[ORM\Column(name: 'description', type: Types::STRING, length: 1000, nullable: true, options: ['comment' => 'Additional information'])]
    private ?string $description = null;

    #[ORM\Column(name: 'create_date', type: Types::DATETIME_IMMUTABLE, options: ['comment' => 'Creation date'])]
    private DateTimeImmutable $createDate;

    #[ORM\Column(name: 'update_date', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'Modification date'])]
    private ?DateTimeImmutable $updateDate = null;

    #[ORM\Column(name: 'warehouse_data', type: Types::TEXT, nullable: true, options: ['comment' => 'Warehouse data: address, name, working hours'])]
    private ?string $warehouseData = null;

    #[ORM\Column(name: 'step', type: Types::BOOLEAN, nullable: false, options: ['default' => true, 'comment' => 'If true, the order will not be reset as a result of changes'])]
    private bool $step = true;

    #[ORM\Column(name: 'address_equal', type: Types::BOOLEAN, nullable: true, options: ['default' => 1, 'comment' => 'Payer and recipient addresses match (false - different, true - the same)'])]
    private ?bool $addressEqual = null;

    #[ORM\Column(name: 'bank_transfer_requested', type: Types::BOOLEAN, nullable: true, options: ['comment' => 'Whether an invoice for a bank transfer was requested'])]
    private ?bool $bankTransferRequested = null;

    #[ORM\Column(name: 'accept_pay', type: Types::BOOLEAN, nullable: true, options: ['comment' => 'If true, the order has been sent to work'])]
    private ?bool $acceptPay = null;

    #[ORM\Column(name: 'cancel_date', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'Final date for agreeing on delivery times'])]
    private ?DateTimeImmutable $cancelDate = null;

    #[ORM\Column(name: 'weight_gross', type: Types::FLOAT, nullable: true, options: ['comment' => 'Total gross weight of the order'])]
    private ?float $weightGross = null;

    #[ORM\Column(name: 'product_review', type: Types::BOOLEAN, nullable: true, options: ['comment' => 'A review was left for the collections in the order'])]
    private ?bool $productReview = null;

    #[ORM\Column(name: 'mirror', type: Types::SMALLINT, nullable: true, options: ['comment' => 'Label of the mirror site the order is created on'])]
    private ?int $mirror = null;

    #[ORM\Column(name: 'process', type: Types::BOOLEAN, nullable: true, options: ['comment' => 'Bulk processing flag'])]
    private ?bool $process = null;

    #[ORM\Column(name: 'fact_date', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'Actual delivery date'])]
    private ?DateTimeImmutable $factDate = null;

    #[ORM\Column(name: 'entrance_review', type: Types::SMALLINT, nullable: true, options: ['comment' => 'Records the client entering the review page and subsequent clicks'])]
    private ?int $entranceReview = null;

    #[ORM\Column(name: 'payment_euro', type: Types::BOOLEAN, nullable: true, options: ['default' => 0, 'comment' => 'If true, calculate the payment in euros'])]
    private ?bool $paymentEuro = null;

    #[ORM\Column(name: 'spec_price', type: Types::BOOLEAN, nullable: true, options: ['comment' => 'A special price is set for the order'])]
    private ?bool $specPrice = null;

    #[ORM\Column(name: 'show_msg', type: Types::BOOLEAN, nullable: true, options: ['comment' => 'Show special message'])]
    private ?bool $showMsg = null;

    #[ORM\Column(name: 'delivery_price_euro', type: Types::FLOAT, nullable: true, options: ['comment' => 'Delivery cost in euros'])]
    private ?float $deliveryPriceEuro = null;

    #[ORM\Column(name: 'address_payer', type: Types::INTEGER, nullable: true)]
    private ?int $addressPayer = null;

    #[ORM\Column(name: 'sending_date', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => 'Calculated delivery date'])]
    private ?DateTimeImmutable $sendingDate = null;

    #[ORM\Column(name: 'delivery_calculate_type', type: Types::SMALLINT, nullable: true, options: ['default' => 0, 'comment' => 'Calculation type: 0 - manual, 1 - automatic'])]
    private ?int $deliveryCalculateType = null;

    #[ORM\Column(name: 'full_payment_date', type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => 'Date of full payment of the order'])]
    private ?DateTimeImmutable $fullPaymentDate = null;

    #[ORM\Column(name: 'bank_details', type: Types::TEXT, nullable: true, options: ['comment' => 'Bank details for refunds'])]
    private ?string $bankDetails = null;

    #[ORM\Column(name: 'delivery_apartment_office', type: Types::STRING, length: 30, nullable: true, options: ['comment' => 'Apartment/office'])]
    private ?string $deliveryApartmentOffice = null;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->setCreateDate(new DateTimeImmutable());
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->setUpdateDate(new DateTimeImmutable());
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): static
    {
        $this->hash = $hash;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getStatus(): ?OrderStatus
    {
        return $this->status;
    }

    public function setStatus(?OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getVatType(): ?VatType
    {
        return $this->vatType;
    }

    public function setVatType(VatType $vatType): static
    {
        $this->vatType = $vatType;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): static
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    public function setTaxNumber(?string $taxNumber): static
    {
        $this->taxNumber = $taxNumber;

        return $this;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    public function setDiscount(?int $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getDelivery(): ?float
    {
        return $this->delivery;
    }

    public function setDelivery(?float $delivery): static
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function getDeliveryType(): ?int
    {
        return $this->deliveryType;
    }

    public function setDeliveryType(?int $deliveryType): static
    {
        $this->deliveryType = $deliveryType;

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

    public function getDeliveryTimeConfirmMin(): ?DateTimeImmutable
    {
        return $this->deliveryTimeConfirmMin;
    }

    public function setDeliveryTimeConfirmMin(?DateTimeImmutable $deliveryTimeConfirmMin): static
    {
        $this->deliveryTimeConfirmMin = $deliveryTimeConfirmMin;

        return $this;
    }

    public function getDeliveryTimeConfirmMax(): ?DateTimeImmutable
    {
        return $this->deliveryTimeConfirmMax;
    }

    public function setDeliveryTimeConfirmMax(?DateTimeImmutable $deliveryTimeConfirmMax): static
    {
        $this->deliveryTimeConfirmMax = $deliveryTimeConfirmMax;

        return $this;
    }

    public function getDeliveryTimeFastPayMin(): ?DateTimeImmutable
    {
        return $this->deliveryTimeFastPayMin;
    }

    public function setDeliveryTimeFastPayMin(?DateTimeImmutable $deliveryTimeFastPayMin): static
    {
        $this->deliveryTimeFastPayMin = $deliveryTimeFastPayMin;

        return $this;
    }

    public function getDeliveryTimeFastPayMax(): ?DateTimeImmutable
    {
        return $this->deliveryTimeFastPayMax;
    }

    public function setDeliveryTimeFastPayMax(?DateTimeImmutable $deliveryTimeFastPayMax): static
    {
        $this->deliveryTimeFastPayMax = $deliveryTimeFastPayMax;

        return $this;
    }

    public function getDeliveryOldTimeMin(): ?DateTimeImmutable
    {
        return $this->deliveryOldTimeMin;
    }

    public function setDeliveryOldTimeMin(?DateTimeImmutable $deliveryOldTimeMin): static
    {
        $this->deliveryOldTimeMin = $deliveryOldTimeMin;

        return $this;
    }

    public function getDeliveryOldTimeMax(): ?DateTimeImmutable
    {
        return $this->deliveryOldTimeMax;
    }

    public function setDeliveryOldTimeMax(?DateTimeImmutable $deliveryOldTimeMax): static
    {
        $this->deliveryOldTimeMax = $deliveryOldTimeMax;

        return $this;
    }

    public function getDeliveryIndex(): ?string
    {
        return $this->deliveryIndex;
    }

    public function setDeliveryIndex(?string $deliveryIndex): static
    {
        $this->deliveryIndex = $deliveryIndex;

        return $this;
    }

    public function getDeliveryCountry(): ?int
    {
        return $this->deliveryCountry;
    }

    public function setDeliveryCountry(?int $deliveryCountry): static
    {
        $this->deliveryCountry = $deliveryCountry;

        return $this;
    }

    public function getDeliveryRegion(): ?string
    {
        return $this->deliveryRegion;
    }

    public function setDeliveryRegion(?string $deliveryRegion): static
    {
        $this->deliveryRegion = $deliveryRegion;

        return $this;
    }

    public function getDeliveryCity(): ?string
    {
        return $this->deliveryCity;
    }

    public function setDeliveryCity(?string $deliveryCity): static
    {
        $this->deliveryCity = $deliveryCity;

        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?string $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function getDeliveryBuilding(): ?string
    {
        return $this->deliveryBuilding;
    }

    public function setDeliveryBuilding(?string $deliveryBuilding): static
    {
        $this->deliveryBuilding = $deliveryBuilding;

        return $this;
    }

    public function getDeliveryPhoneCode(): ?string
    {
        return $this->deliveryPhoneCode;
    }

    public function setDeliveryPhoneCode(?string $deliveryPhoneCode): static
    {
        $this->deliveryPhoneCode = $deliveryPhoneCode;

        return $this;
    }

    public function getDeliveryPhone(): ?string
    {
        return $this->deliveryPhone;
    }

    public function setDeliveryPhone(?string $deliveryPhone): static
    {
        $this->deliveryPhone = $deliveryPhone;

        return $this;
    }

    public function getSex(): ?int
    {
        return $this->sex;
    }

    public function setSex(?int $sex): static
    {
        $this->sex = $sex;

        return $this;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(?string $clientName): static
    {
        $this->clientName = $clientName;

        return $this;
    }

    public function getClientSurname(): ?string
    {
        return $this->clientSurname;
    }

    public function setClientSurname(?string $clientSurname): static
    {
        $this->clientSurname = $clientSurname;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getPayType(): ?PaymentType
    {
        return $this->payType;
    }

    public function setPayType(PaymentType $payType): static
    {
        $this->payType = $payType;

        return $this;
    }

    public function getPayDateExecution(): ?DateTimeImmutable
    {
        return $this->payDateExecution;
    }

    public function setPayDateExecution(?DateTimeImmutable $payDateExecution): static
    {
        $this->payDateExecution = $payDateExecution;

        return $this;
    }

    public function getOffsetDate(): ?DateTimeImmutable
    {
        return $this->offsetDate;
    }

    public function setOffsetDate(?DateTimeImmutable $offsetDate): static
    {
        $this->offsetDate = $offsetDate;

        return $this;
    }

    public function getOffsetReason(): ?int
    {
        return $this->offsetReason;
    }

    public function setOffsetReason(?int $offsetReason): static
    {
        $this->offsetReason = $offsetReason;

        return $this;
    }

    public function getProposedDate(): ?DateTimeImmutable
    {
        return $this->proposedDate;
    }

    public function setProposedDate(?DateTimeImmutable $proposedDate): static
    {
        $this->proposedDate = $proposedDate;

        return $this;
    }

    public function getShipDate(): ?DateTimeImmutable
    {
        return $this->shipDate;
    }

    public function setShipDate(?DateTimeImmutable $shipDate): static
    {
        $this->shipDate = $shipDate;

        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    public function getManagerName(): ?string
    {
        return $this->managerName;
    }

    public function setManagerName(?string $managerName): static
    {
        $this->managerName = $managerName;

        return $this;
    }

    public function getManagerEmail(): ?string
    {
        return $this->managerEmail;
    }

    public function setManagerEmail(?string $managerEmail): static
    {
        $this->managerEmail = $managerEmail;

        return $this;
    }

    public function getManagerPhone(): ?string
    {
        return $this->managerPhone;
    }

    public function setManagerPhone(?string $managerPhone): static
    {
        $this->managerPhone = $managerPhone;

        return $this;
    }

    public function getCarrierName(): ?string
    {
        return $this->carrierName;
    }

    public function setCarrierName(?string $carrierName): static
    {
        $this->carrierName = $carrierName;

        return $this;
    }

    public function getCarrierContactData(): ?string
    {
        return $this->carrierContactData;
    }

    public function setCarrierContactData(?string $carrierContactData): static
    {
        $this->carrierContactData = $carrierContactData;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getCurRate(): ?float
    {
        return $this->curRate;
    }

    public function setCurRate(?float $curRate): static
    {
        $this->curRate = $curRate;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getMeasure(): ?Measure
    {
        return $this->measure;
    }

    public function setMeasure(Measure $measure): static
    {
        $this->measure = $measure;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCreateDate(): ?DateTimeImmutable
    {
        return $this->createDate;
    }

    public function setCreateDate(DateTimeImmutable $createDate): static
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getUpdateDate(): ?DateTimeImmutable
    {
        return $this->updateDate;
    }

    public function setUpdateDate(?DateTimeImmutable $updateDate): static
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getWarehouseData(): ?string
    {
        return $this->warehouseData;
    }

    public function setWarehouseData(?string $warehouseData): static
    {
        $this->warehouseData = $warehouseData;

        return $this;
    }

    public function isStep(): ?bool
    {
        return $this->step;
    }

    public function setStep(bool $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function isAddressEqual(): ?bool
    {
        return $this->addressEqual;
    }

    public function setAddressEqual(?bool $addressEqual): static
    {
        $this->addressEqual = $addressEqual;

        return $this;
    }

    public function isBankTransferRequested(): ?bool
    {
        return $this->bankTransferRequested;
    }

    public function setBankTransferRequested(?bool $bankTransferRequested): static
    {
        $this->bankTransferRequested = $bankTransferRequested;

        return $this;
    }

    public function isAcceptPay(): ?bool
    {
        return $this->acceptPay;
    }

    public function setAcceptPay(?bool $acceptPay): static
    {
        $this->acceptPay = $acceptPay;

        return $this;
    }

    public function getCancelDate(): ?DateTimeImmutable
    {
        return $this->cancelDate;
    }

    public function setCancelDate(?DateTimeImmutable $cancelDate): static
    {
        $this->cancelDate = $cancelDate;

        return $this;
    }

    public function getWeightGross(): ?float
    {
        return $this->weightGross;
    }

    public function setWeightGross(?float $weightGross): static
    {
        $this->weightGross = $weightGross;

        return $this;
    }

    public function isProductReview(): ?bool
    {
        return $this->productReview;
    }

    public function setProductReview(?bool $productReview): static
    {
        $this->productReview = $productReview;

        return $this;
    }

    public function getMirror(): ?int
    {
        return $this->mirror;
    }

    public function setMirror(?int $mirror): static
    {
        $this->mirror = $mirror;

        return $this;
    }

    public function isProcess(): ?bool
    {
        return $this->process;
    }

    public function setProcess(?bool $process): static
    {
        $this->process = $process;

        return $this;
    }

    public function getFactDate(): ?DateTimeImmutable
    {
        return $this->factDate;
    }

    public function setFactDate(?DateTimeImmutable $factDate): static
    {
        $this->factDate = $factDate;

        return $this;
    }

    public function getEntranceReview(): ?int
    {
        return $this->entranceReview;
    }

    public function setEntranceReview(?int $entranceReview): static
    {
        $this->entranceReview = $entranceReview;

        return $this;
    }

    public function isPaymentEuro(): ?bool
    {
        return $this->paymentEuro;
    }

    public function setPaymentEuro(?bool $paymentEuro): static
    {
        $this->paymentEuro = $paymentEuro;

        return $this;
    }

    public function isSpecPrice(): ?bool
    {
        return $this->specPrice;
    }

    public function setSpecPrice(?bool $specPrice): static
    {
        $this->specPrice = $specPrice;

        return $this;
    }

    public function isShowMsg(): ?bool
    {
        return $this->showMsg;
    }

    public function setShowMsg(?bool $showMsg): static
    {
        $this->showMsg = $showMsg;

        return $this;
    }

    public function getDeliveryPriceEuro(): ?float
    {
        return $this->deliveryPriceEuro;
    }

    public function setDeliveryPriceEuro(?float $deliveryPriceEuro): static
    {
        $this->deliveryPriceEuro = $deliveryPriceEuro;

        return $this;
    }

    public function getAddressPayer(): ?int
    {
        return $this->addressPayer;
    }

    public function setAddressPayer(?int $addressPayer): static
    {
        $this->addressPayer = $addressPayer;

        return $this;
    }

    public function getSendingDate(): ?DateTimeImmutable
    {
        return $this->sendingDate;
    }

    public function setSendingDate(?DateTimeImmutable $sendingDate): static
    {
        $this->sendingDate = $sendingDate;

        return $this;
    }

    public function getDeliveryCalculateType(): ?int
    {
        return $this->deliveryCalculateType;
    }

    public function setDeliveryCalculateType(?int $deliveryCalculateType): static
    {
        $this->deliveryCalculateType = $deliveryCalculateType;

        return $this;
    }

    public function getFullPaymentDate(): ?DateTimeImmutable
    {
        return $this->fullPaymentDate;
    }

    public function setFullPaymentDate(?DateTimeImmutable $fullPaymentDate): static
    {
        $this->fullPaymentDate = $fullPaymentDate;

        return $this;
    }

    public function getBankDetails(): ?string
    {
        return $this->bankDetails;
    }

    public function setBankDetails(?string $bankDetails): static
    {
        $this->bankDetails = $bankDetails;

        return $this;
    }

    public function getDeliveryApartmentOffice(): ?string
    {
        return $this->deliveryApartmentOffice;
    }

    public function setDeliveryApartmentOffice(?string $deliveryApartmentOffice): static
    {
        $this->deliveryApartmentOffice = $deliveryApartmentOffice;

        return $this;
    }

    /**
     * @return Collection<int, OrderArticle>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(OrderArticle $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setOrder($this);
        }

        return $this;
    }

    public function removeArticle(OrderArticle $article): static
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getOrder() === $this) {
                $article->setOrder(null);
            }
        }

        return $this;
    }
}
