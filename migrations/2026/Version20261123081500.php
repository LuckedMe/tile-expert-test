<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Database improvements, see docs/db-dump-analysis.md.
 *
 * The migration fails (and changes nothing that matters) when the data violates a new rule:
 * duplicated order hashes or articles without an order must be fixed by hand first.
 */
final class Version20261123081500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Unique order hash, decimal money/quantities, NOT NULL order of an article, check constraints, wider manager columns';
    }

    public function up(Schema $schema): void
    {
        // orders: indexes
        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders`
            DROP INDEX `idx_hash`,
            DROP INDEX `idx_create_date`,

            ADD UNIQUE INDEX `uniq_orders_hash` (`hash`)
        '
        );

        // orders: money, rates and weights are exact decimals now (DOUBLE rounds 0.1 + 0.2)
        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders`
            CHANGE `discount`            `discount`            SMALLINT UNSIGNED DEFAULT NULL COMMENT \'Discount percentage, 0..100\',
            CHANGE `delivery`            `delivery`            NUMERIC(10, 2)    DEFAULT NULL COMMENT \'Delivery cost\',
            CHANGE `delivery_price_euro` `delivery_price_euro` NUMERIC(10, 2)    DEFAULT NULL COMMENT \'Delivery cost in euros\',
            CHANGE `cur_rate`            `cur_rate`            NUMERIC(12, 6)    DEFAULT 1.000000 COMMENT \'Exchange rate at the time of payment\',
            CHANGE `weight_gross`        `weight_gross`        NUMERIC(12, 3)    DEFAULT NULL COMMENT \'Total gross weight of the order\',

            CHANGE `manager_name`        `manager_name`        VARCHAR(100)      DEFAULT NULL COMMENT \'Name of the manager handling the order\',
            CHANGE `manager_email`       `manager_email`       VARCHAR(255)      DEFAULT NULL COMMENT \'Email of the manager handling the order\',
            CHANGE `manager_phone`       `manager_phone`       VARCHAR(50)       DEFAULT NULL COMMENT \'Phone of the manager handling the order\'
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders`
            ADD CONSTRAINT `chk_orders_discount` CHECK (`discount` IS NULL OR `discount` BETWEEN 0 AND 100)
        '
        );

        // orders_article: an article always belongs to an order
        // (MySQL refuses to change a column used in a foreign key, so the key is recreated)
        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders_article`
            DROP FOREIGN KEY `FK_F34F7C1DCFFE9AD6`
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders_article`
            CHANGE `orders_id`       `orders_id`       INT            NOT NULL,

            CHANGE `amount`          `amount`          NUMERIC(12, 3) NOT NULL COMMENT \'Number of articles in the unit of measure\',
            CHANGE `price`           `price`           NUMERIC(12, 2) NOT NULL COMMENT \'Price at the time of order payment\',
            CHANGE `price_eur`       `price_eur`       NUMERIC(12, 2) DEFAULT NULL COMMENT \'Order price in euros\',
            CHANGE `weight`          `weight`          NUMERIC(10, 3) NOT NULL COMMENT \'Package weight\',
            CHANGE `packaging_count` `packaging_count` NUMERIC(10, 3) NOT NULL COMMENT \'Quantity multiple in which the product can be added to the order\',
            CHANGE `pallet`          `pallet`          NUMERIC(10, 3) NOT NULL COMMENT \'Quantity per pallet at the time of the order\',
            CHANGE `packaging`       `packaging`       NUMERIC(10, 3) NOT NULL COMMENT \'Quantity per package\'
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders_article`
            ADD CONSTRAINT `FK_F34F7C1DCFFE9AD6` FOREIGN KEY (`orders_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,

            ADD CONSTRAINT `chk_article_amount_positive` CHECK (`amount` > 0),
            ADD CONSTRAINT `chk_article_price_not_negative` CHECK (`price` >= 0)
        '
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders_article`
            DROP CONSTRAINT `chk_article_amount_positive`,
            DROP CONSTRAINT `chk_article_price_not_negative`
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders_article`
            DROP FOREIGN KEY `FK_F34F7C1DCFFE9AD6`
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders_article`
            CHANGE `orders_id`       `orders_id`       INT              DEFAULT NULL,

            CHANGE `amount`          `amount`          DOUBLE PRECISION NOT NULL COMMENT \'Number of articles in the unit of measure\',
            CHANGE `price`           `price`           DOUBLE PRECISION NOT NULL COMMENT \'Price at the time of order payment\',
            CHANGE `price_eur`       `price_eur`       DOUBLE PRECISION DEFAULT NULL COMMENT \'Order price in euros\',
            CHANGE `weight`          `weight`          DOUBLE PRECISION NOT NULL COMMENT \'Package weight\',
            CHANGE `packaging_count` `packaging_count` DOUBLE PRECISION NOT NULL COMMENT \'Quantity multiple in which the product can be added to the order\',
            CHANGE `pallet`          `pallet`          DOUBLE PRECISION NOT NULL COMMENT \'Quantity per pallet at the time of the order\',
            CHANGE `packaging`       `packaging`       DOUBLE PRECISION NOT NULL COMMENT \'Quantity per package\'
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders_article`
            ADD CONSTRAINT `FK_F34F7C1DCFFE9AD6` FOREIGN KEY (`orders_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders`
            DROP CONSTRAINT `chk_orders_discount`
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders`
            CHANGE `discount`            `discount`            SMALLINT         DEFAULT NULL COMMENT \'Discount percentage\',
            CHANGE `delivery`            `delivery`            DOUBLE PRECISION DEFAULT NULL COMMENT \'Delivery cost\',
            CHANGE `delivery_price_euro` `delivery_price_euro` DOUBLE PRECISION DEFAULT NULL COMMENT \'Delivery cost in euros\',
            CHANGE `cur_rate`            `cur_rate`            DOUBLE PRECISION DEFAULT 1    COMMENT \'Exchange rate at the time of payment\',
            CHANGE `weight_gross`        `weight_gross`        DOUBLE PRECISION DEFAULT NULL COMMENT \'Total gross weight of the order\',

            CHANGE `manager_name`        `manager_name`        VARCHAR(20)      DEFAULT NULL COMMENT \'Name of the manager handling the order\',
            CHANGE `manager_email`       `manager_email`       VARCHAR(30)      DEFAULT NULL COMMENT \'Email of the manager handling the order\',
            CHANGE `manager_phone`       `manager_phone`       VARCHAR(20)      DEFAULT NULL COMMENT \'Phone of the manager handling the order\'
        '
        );

        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders`
            DROP INDEX `uniq_orders_hash`,

            ADD INDEX `idx_hash`        (`hash`),
            ADD INDEX `idx_create_date` (`create_date`)
        '
        );
    }
}
