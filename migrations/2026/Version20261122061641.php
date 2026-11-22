<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261122061641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders`
            CHANGE `status` `status` ENUM(\'created\', \'canceled\', \'confirmed\', \'paid\', \'pickup_ready\') DEFAULT \'created\' NOT NULL COMMENT \'Order status\',
            CHANGE `vat_type` `vat_type` ENUM(\'vat-type-1\', \'vat-type-2\', \'vat-type-3\') DEFAULT \'vat-type-1\' NOT NULL COMMENT \'Private person or VAT payer\',
            CHANGE `pay_type` `pay_type` ENUM(\'visa\', \'mastercard\', \'paypal\') DEFAULT \'visa\' NOT NULL COMMENT \'Selected payment type\',
            CHANGE `currency` `currency` ENUM(\'USD\', \'EUR\') DEFAULT \'EUR\' NOT NULL COMMENT \'Currency the order was placed in\',
            CHANGE `measure` `measure` ENUM(\'m\', \'mm\', \'sm\', \'kg\') DEFAULT \'m\' NOT NULL COMMENT \'Unit of measure the order was placed in\',
            CHANGE `step` `step` TINYINT DEFAULT 1 NOT NULL COMMENT \'If true, the order will not be reset as a result of changes\'
        '
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
        /** @lang MySQL */ '
        ALTER TABLE `orders`
            CHANGE `status` `status` INT DEFAULT 1 NOT NULL COMMENT \'Order status\',
            CHANGE `vat_type` `vat_type` INT DEFAULT 0 NOT NULL COMMENT \'Private person or VAT payer\',
            CHANGE `pay_type` `pay_type` SMALLINT NOT NULL COMMENT \'Selected payment type\',
            CHANGE `currency` `currency` VARCHAR(3) DEFAULT \'EUR\' NOT NULL COMMENT \'Currency the order was placed in\',
            CHANGE `measure` `measure` VARCHAR(3) DEFAULT \'m\' NOT NULL COMMENT \'Unit of measure the order was placed in\',
            CHANGE `step` `step` SMALLINT DEFAULT 1 NOT NULL COMMENT \'If true, the order will not be reset as a result of changes\'
        '
        );
    }
}
