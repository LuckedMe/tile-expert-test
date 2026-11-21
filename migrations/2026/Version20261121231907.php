<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261121231907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
        /** @lang MySQL */ '
        CREATE TABLE `orders` (
            `id`                         INT AUTO_INCREMENT NOT NULL,
            `hash`                       VARCHAR(32) NOT NULL COMMENT \'Order hash\',
            `user_id`                    INT DEFAULT NULL,
            `token`                      VARCHAR(64) NOT NULL COMMENT \'Unique user hash\',
            `number`                     VARCHAR(10) DEFAULT NULL COMMENT \'Order number\',
            `status`                     INT DEFAULT 1 NOT NULL COMMENT \'Order status\',
            `email`                      VARCHAR(100) DEFAULT NULL COMMENT \'Contact email\',
            `vat_type`                   INT DEFAULT 0 NOT NULL COMMENT \'Private person or VAT payer\',
            `vat_number`                 VARCHAR(100) DEFAULT NULL COMMENT \'VAT number\',
            `tax_number`                 VARCHAR(50) DEFAULT NULL COMMENT \'Individual taxpayer identification number\',
            `discount`                   SMALLINT DEFAULT NULL COMMENT \'Discount percentage\',
            `delivery`                   DOUBLE PRECISION DEFAULT NULL COMMENT \'Delivery cost\',
            `delivery_type`              SMALLINT DEFAULT 0 COMMENT \'Delivery type: 0 - client address, 1 - warehouse address\',
            `delivery_time_min`          DATE DEFAULT NULL COMMENT \'Minimum delivery time\',
            `delivery_time_max`          DATE DEFAULT NULL COMMENT \'Maximum delivery time\',
            `delivery_time_confirm_min`  DATE DEFAULT NULL COMMENT \'Minimum delivery time confirmed by the manufacturer\',
            `delivery_time_confirm_max`  DATE DEFAULT NULL COMMENT \'Maximum delivery time confirmed by the manufacturer\',
            `delivery_time_fast_pay_min` DATE DEFAULT NULL COMMENT \'Minimum delivery time\',
            `delivery_time_fast_pay_max` DATE DEFAULT NULL COMMENT \'Maximum delivery time\',
            `delivery_old_time_min`      DATE DEFAULT NULL COMMENT \'Previous minimum delivery time\',
            `delivery_old_time_max`      DATE DEFAULT NULL COMMENT \'Previous maximum delivery time\',
            `delivery_index`             VARCHAR(20) DEFAULT NULL,
            `delivery_country`           INT DEFAULT NULL,
            `delivery_region`            VARCHAR(50) DEFAULT NULL,
            `delivery_city`              VARCHAR(200) DEFAULT NULL,
            `delivery_address`           VARCHAR(300) DEFAULT NULL,
            `delivery_building`          VARCHAR(200) DEFAULT NULL,
            `delivery_phone_code`        VARCHAR(20) DEFAULT NULL,
            `delivery_phone`             VARCHAR(20) DEFAULT NULL,
            `sex`                        SMALLINT DEFAULT NULL COMMENT \'Client gender\',
            `client_name`                VARCHAR(255) DEFAULT NULL COMMENT \'Client first name\',
            `client_surname`             VARCHAR(255) DEFAULT NULL COMMENT \'Client last name\',
            `company_name`               VARCHAR(255) DEFAULT NULL COMMENT \'Company name\',
            `pay_type`                   SMALLINT NOT NULL COMMENT \'Selected payment type\',
            `pay_date_execution`         DATETIME DEFAULT NULL COMMENT \'Date until which the current order price is valid\',
            `offset_date`                DATETIME DEFAULT NULL COMMENT \'Date of shift of the estimated delivery calculation\',
            `offset_reason`              SMALLINT DEFAULT NULL COMMENT \'Reason type for the deadline shift: 1 - factory holidays, 2 - factory is clarifying production times, 3 - other\',
            `proposed_date`              DATETIME DEFAULT NULL COMMENT \'Estimated delivery date\',
            `ship_date`                  DATETIME DEFAULT NULL COMMENT \'Estimated shipping date\',
            `tracking_number`            VARCHAR(50) DEFAULT NULL COMMENT \'Tracking number\',
            `manager_name`               VARCHAR(20) DEFAULT NULL COMMENT \'Name of the manager handling the order\',
            `manager_email`              VARCHAR(30) DEFAULT NULL COMMENT \'Email of the manager handling the order\',
            `manager_phone`              VARCHAR(20) DEFAULT NULL COMMENT \'Phone of the manager handling the order\',
            `carrier_name`               VARCHAR(50) DEFAULT NULL COMMENT \'Carrier company name\',
            `carrier_contact_data`       VARCHAR(255) DEFAULT NULL COMMENT \'Carrier company contact details\',
            `locale`                     VARCHAR(5) NOT NULL COMMENT \'Locale the order was placed from\',
            `cur_rate`                   DOUBLE PRECISION DEFAULT 1 COMMENT \'Exchange rate at the time of payment\',
            `currency`                   VARCHAR(3) DEFAULT \'EUR\' NOT NULL COMMENT \'Currency the order was placed in\',
            `measure`                    VARCHAR(3) DEFAULT \'m\' NOT NULL COMMENT \'Unit of measure the order was placed in\',
            `name`                       VARCHAR(200) NOT NULL COMMENT \'Order name\',
            `description`                VARCHAR(1000) DEFAULT NULL COMMENT \'Additional information\',
            `create_date`                DATETIME NOT NULL COMMENT \'Creation date\',
            `update_date`                DATETIME DEFAULT NULL COMMENT \'Modification date\',
            `warehouse_data`             LONGTEXT DEFAULT NULL COMMENT \'Warehouse data: address, name, working hours\',
            `step`                       SMALLINT DEFAULT 1 NOT NULL COMMENT \'If true, the order will not be reset as a result of changes\',
            `address_equal`              TINYINT DEFAULT 1 COMMENT \'Payer and recipient addresses match (false - different, true - the same)\',
            `bank_transfer_requested`    TINYINT DEFAULT NULL COMMENT \'Whether an invoice for a bank transfer was requested\',
            `accept_pay`                 TINYINT DEFAULT NULL COMMENT \'If true, the order has been sent to work\',
            `cancel_date`                DATETIME DEFAULT NULL COMMENT \'Final date for agreeing on delivery times\',
            `weight_gross`               DOUBLE PRECISION DEFAULT NULL COMMENT \'Total gross weight of the order\',
            `product_review`             TINYINT DEFAULT NULL COMMENT \'A review was left for the collections in the order\',
            `mirror`                     SMALLINT DEFAULT NULL COMMENT \'Label of the mirror site the order is created on\',
            `process`                    TINYINT DEFAULT NULL COMMENT \'Bulk processing flag\',
            `fact_date`                  DATETIME DEFAULT NULL COMMENT \'Actual delivery date\',
            `entrance_review`            SMALLINT DEFAULT NULL COMMENT \'Records the client entering the review page and subsequent clicks\',
            `payment_euro`               TINYINT DEFAULT 0 COMMENT \'If true, calculate the payment in euros\',
            `spec_price`                 TINYINT DEFAULT NULL COMMENT \'A special price is set for the order\',
            `show_msg`                   TINYINT DEFAULT NULL COMMENT \'Show special message\',
            `delivery_price_euro`        DOUBLE PRECISION DEFAULT NULL COMMENT \'Delivery cost in euros\',
            `address_payer`              INT DEFAULT NULL,
            `sending_date`               DATETIME DEFAULT NULL COMMENT \'Calculated delivery date\',
            `delivery_calculate_type`    SMALLINT DEFAULT 0 COMMENT \'Calculation type: 0 - manual, 1 - automatic\',
            `full_payment_date`          DATE DEFAULT NULL COMMENT \'Date of full payment of the order\',
            `bank_details`               LONGTEXT DEFAULT NULL COMMENT \'Bank details for refunds\',
            `delivery_apartment_office`  VARCHAR(30) DEFAULT NULL COMMENT \'Apartment/office\',

            INDEX `idx_delivery_country`   (`delivery_country`),
            INDEX `idx_user_id`            (`user_id`),
            INDEX `idx_create_date`        (`create_date`),
            INDEX `idx_create_date_status` (`create_date`, `status`),
            INDEX `idx_hash`               (`hash`),
            PRIMARY KEY                    (`id`)

        ) DEFAULT CHARACTER SET utf8mb4
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        CREATE TABLE `orders_article` (
            `id`                INT AUTO_INCREMENT NOT NULL,
            `orders_id`         INT DEFAULT NULL,
            `article_id`        INT DEFAULT NULL COMMENT \'Collection ID\',
            `amount`            DOUBLE PRECISION NOT NULL COMMENT \'Number of articles in the unit of measure\',
            `price`             DOUBLE PRECISION NOT NULL COMMENT \'Price at the time of order payment\',
            `price_eur`         DOUBLE PRECISION DEFAULT NULL COMMENT \'Order price in euros\',
            `currency`          VARCHAR(3) DEFAULT NULL COMMENT \'Currency the price is set for\',
            `measure`           VARCHAR(2) DEFAULT NULL COMMENT \'Unit of measure the price is set for\',
            `delivery_time_min` DATE DEFAULT NULL COMMENT \'Minimum delivery time\',
            `delivery_time_max` DATE DEFAULT NULL COMMENT \'Maximum delivery time\',
            `weight`            DOUBLE PRECISION NOT NULL COMMENT \'Package weight\',
            `multiple_pallet`   SMALLINT DEFAULT NULL COMMENT \'Pallet multiple: 1 - multiple of a package, 2 - multiple of a pallet, 3 - not less than a pallet\',
            `packaging_count`   DOUBLE PRECISION NOT NULL COMMENT \'Quantity multiple in which the product can be added to the order\',
            `pallet`            DOUBLE PRECISION NOT NULL COMMENT \'Quantity per pallet at the time of the order\',
            `packaging`         DOUBLE PRECISION NOT NULL COMMENT \'Quantity per package\',
            `swimming_pool`     TINYINT DEFAULT 0 NOT NULL COMMENT \'Tile specifically for swimming pools\',

            INDEX `fk_orders_id`   (`orders_id`),
            INDEX `idx_article_id` (`article_id`),
            PRIMARY KEY            (`id`)

        ) DEFAULT CHARACTER SET utf8mb4
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders_article`
            ADD CONSTRAINT `FK_F34F7C1DCFFE9AD6`
                FOREIGN KEY (`orders_id`)
                REFERENCES `orders` (`id`)
                ON DELETE CASCADE
        '
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders_article`
            DROP FOREIGN KEY `FK_F34F7C1DCFFE9AD6`
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        DROP TABLE `orders`
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        DROP TABLE `orders_article`
        '
        );
    }
}
